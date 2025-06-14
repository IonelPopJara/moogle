#![allow(warnings)]

extern crate redis;
use redis::Commands;
use std::env;
use std::process;
use std::process::Command;
use std::{thread, time};

fn main() {
    // Get environment variable
    let redis_host = env::var("REDIS_HOST").expect("REDIS_HOST environment variable not set");
    let redis_port = env::var("REDIS_PORT").unwrap_or_else(|_| "6379".to_string());
    let redis_password = env::var("REDIS_PASSWORD").unwrap_or_else(|_| "".to_string());
    let redis_db: u8 = env::var("REDIS_DB")
        .unwrap_or("0".to_string())
        .parse()
        .expect("REDIS_DB must be a valid integer");

    // Build the Redis URL
    let mut redis_url = format!("redis://{}:{}", redis_host, redis_port);

    // Add password if available
    if !redis_password.is_empty() {
        redis_url = format!("redis://:{}@{}:{}", redis_password, redis_host, redis_port);
    }

    // Add the DB number
    redis_url = format!("{}/{}", redis_url, redis_db);

    println!("Connecting to Redis at: {}...", redis_url);
    // Connect to redis
    let client = redis::Client::open(redis_url).expect("Invalid Redis URL");
    let mut con = client.get_connection().expect("Failed to connect to Redis");

    // Get command line arguments for max services
    let args: Vec<String> = env::args().collect();

    let max_spiders = if args.len() > 1 {
        args[1].parse::<usize>().unwrap_or(10) // Changed default to 10
    } else {
        10 // Default to 10
    };

    let max_indexers = if args.len() > 2 {
        args[2].parse::<usize>().unwrap_or(10)
    } else {
        10 // Default to 10
    };

    let max_backlinks_processors = if args.len() > 3 {
        args[3].parse::<usize>().unwrap_or(5)
    } else {
        5 // Default to 5
    };

    loop {
        // Check for termination signal
        if let Ok(Some(message)) = con.rpop::<_, Option<String>>("terminator_queue", None) {
            if message == "TERMINATE_SERVICES" {
                println!("Received termination signal. Shutting down services...");
                terminate_services();
                break;
            }
        }

        // Get the length of the url_queue
        let url_queue_length: usize = con
            .zcard("spider_queue")
            .expect("Failed to get queue length");
        // Get the length of the indexer_queue
        let indexer_queue_length: usize =
            con.llen("pages_queue").expect("Failed to get queue length");
        // Get the count of backlinks keys
        let backlinks_count: usize = get_backlinks_count(&mut con);

        // Print the queue lengths and backlinks count
        println!("|--------------------------------------|");
        println!("| Current url_queue length: {}", url_queue_length);
        println!("| Current indexer_queue length: {}", indexer_queue_length);
        println!("| Current backlinks count: {}", backlinks_count);
        println!("|--------------------------------------|");

        // Scale spiders
        let desired_spiders = {
            if indexer_queue_length >= 1000 {
                0 // No spiders when queue reaches 1000 or more
            } else {
                let scale_factor = 1.0 - (indexer_queue_length as f64 / 1000.0);
                (max_spiders as f64 * scale_factor).round() as usize
            }
        };

        let current_spiders = get_current_spiders();
        println!("| Current spiders: {}", current_spiders);
        println!("|\tDesired spider count: {}", desired_spiders);
        scale_spiders(desired_spiders);

        // Scale indexers
        let desired_indexers = {
            if indexer_queue_length < 50 {
                1
            } else {
                std::cmp::min(
                    max_indexers,
                    (1.5 * (indexer_queue_length as f64 / 100.0)).ceil() as usize,
                )
            }
        };

        let desired_indexers = std::cmp::min(desired_indexers, max_indexers);
        let current_indexers = get_current_indexers();
        println!("| Current indexers: {}", current_indexers);
        println!("|\tDesired indexer count: {}", desired_indexers);
        scale_indexers(desired_indexers);

        // Scale backlinks processors
        let desired_backlinks_processors =
            calculate_desired_backlinks_processors(backlinks_count, max_backlinks_processors);
        let current_backlinks_processors = get_current_backlinks_processors();
        println!(
            "| Current backlinks processors: {}",
            current_backlinks_processors
        );
        println!(
            "|\tDesired backlinks processors count: {}",
            desired_backlinks_processors
        );
        scale_backlinks_processors(desired_backlinks_processors);

        println!("|--------------------------------------|");
        println!("");

        // Sleep
        for _i in 0..5 {
            thread::sleep(time::Duration::from_millis(1000));
            // Check for termination signal
            if let Ok(Some(message)) = con.rpop::<_, Option<String>>("terminator_queue", None) {
                if message == "TERMINATE_SERVICES" {
                    println!("Received termination signal. Shutting down services...");
                    terminate_services();
                }
            }
        }
    }
}

fn get_backlinks_count(con: &mut redis::Connection) -> usize {
    // Use KEYS command to get all keys matching the pattern "backlinks:*"
    // Note: In production, SCAN should be used instead of KEYS for large databases
    let keys: Vec<String> = redis::cmd("KEYS")
        .arg("backlinks:*")
        .query(con)
        .unwrap_or_else(|_| Vec::new());

    keys.len()
}

fn calculate_desired_backlinks_processors(backlinks_count: usize, max_processors: usize) -> usize {
    if backlinks_count < 1000 {
        1
    } else {
        // Scale linearly from 1 to max_processors
        let scale_factor = (backlinks_count as f64 / 50000.0).min(1.0);
        let calculated = 1 + ((max_processors - 1) as f64 * scale_factor).round() as usize;
        std::cmp::min(calculated, max_processors)
    }
}

fn scale_backlinks_processors(desired_processors: usize) {
    let current_processors = get_current_backlinks_processors();

    if current_processors != desired_processors {
        println!("Scaling backlinks processors to: {}", desired_processors);
        Command::new("docker")
            .arg("compose")
            .arg("-f")
            .arg("../backlinks-processor/docker-compose.yml") // Specify the backlinks service compose file
            .arg("up")
            //.arg("--scale")
            //.arg(format!("backlinks-processor={}", desired_processors))
            .arg("-d")
            .status()
            .expect("Failed to scale backlinks processors");
    }
}

fn get_current_backlinks_processors() -> usize {
    let output = Command::new("docker")
        .arg("compose")
        .arg("-f")
        .arg("../backlinks-processor/docker-compose.yml") // Specify the backlinks service compose file
        .arg("ps")
        .output()
        .expect("Failed to get Docker Compose services");

    let output_str = String::from_utf8_lossy(&output.stdout);

    output_str
        .split('\n')
        .filter(|line| line.contains("backlinks-processor"))
        .count()
}

fn stop_backlinks_processors() {
    println!("Stopping backlinks processors...");
    Command::new("docker")
        .arg("compose")
        .arg("-f")
        .arg("../backlinks-processor/docker-compose.yml") // Specify the backlinks service compose file
        .arg("down")
        .status()
        .expect("Failed to stop backlinks processors");
}

fn terminate_services() {
    println!("Terminating services...");
    stop_indexers();
    stop_spiders();
    stop_backlinks_processors();
    process::exit(0);
}

fn scale_spiders(desired_spiders: usize) {
    let current_spiders = get_current_spiders();

    if current_spiders != desired_spiders {
        println!("Scaling spiders to: {}", desired_spiders);
        Command::new("docker")
            .arg("compose")
            .arg("-f")
            .arg("../spider/docker-compose.yml") // Specify the indexer service compose file
            .arg("up")
            .arg("--scale")
            .arg(format!("spider-service={}", desired_spiders))
            .arg("-d")
            .status()
            .expect("Failed to scale spiders");
    }
}

fn get_current_spiders() -> usize {
    let output = Command::new("docker")
        .arg("compose")
        .arg("-f")
        .arg("../spider/docker-compose.yml") // Specify the indexer service compose file
        .arg("ps")
        .output()
        .expect("Failed to get Docker Compose services");

    let output_str = String::from_utf8_lossy(&output.stdout);

    output_str
        .split('\n')
        .filter(|line| line.contains("spider-service"))
        .count()
}

fn scale_indexers(desired_indexers: usize) {
    let current_indexers = get_current_indexers();

    if current_indexers != desired_indexers {
        println!("Scaling indexers to: {}", desired_indexers);
        Command::new("docker")
            .arg("compose")
            .arg("-f")
            .arg("../indexer/docker-compose.yml") // Specify the indexer service compose file
            .arg("up")
            .arg("--scale")
            .arg(format!("indexer-service={}", desired_indexers))
            .arg("-d")
            .status()
            .expect("Failed to scale indexers");
    }
}

fn stop_indexers() {
    println!("Stopping indexers...");
    Command::new("docker")
        .arg("compose")
        .arg("-f")
        .arg("../indexer/docker-compose.yml") // Specify the indexer service compose file
        .arg("down")
        .status()
        .expect("Failed to stop indexers");
}

fn stop_spiders() {
    println!("Stopping spiders...");
    Command::new("docker")
        .arg("compose")
        .arg("-f")
        .arg("../spider/docker-compose.yml") // Specify the indexer service compose file
        .arg("down")
        .status()
        .expect("Failed to stop spiders");
}

fn get_current_indexers() -> usize {
    let output = Command::new("docker")
        .arg("compose")
        .arg("-f")
        .arg("../indexer/docker-compose.yml") // Specify the indexer service compose file
        .arg("ps")
        .output()
        .expect("Failed to get Docker Compose services");

    let output_str = String::from_utf8_lossy(&output.stdout);

    output_str
        .split('\n')
        .filter(|line| line.contains("indexer-service"))
        .count()
}
