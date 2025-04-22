import time
import redis
import logging
from utils.constants import *

from typing import Optional, List
from models.page import Page
from models.metadata import Metadata
from models.image import Image
from models.outlinks import Outlinks

# SETUP LOGGER
logger = logging.getLogger(__name__)
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

class RedisClient:
    def __init__(self, host='localhost', port=6379, password="", db=0, decode_responses=True):
        try:
            self.client = redis.Redis(
                host=host,
                port=port,
                password=password,
                db = db,
                decode_responses=decode_responses
            )

            self.client.ping()
            logger.info('Successfully connected to redis!')
        except Exception as e:
            logger.error(f'Failed to connect to redis')
            self.client = None

    # --------------------- MESSAGE QUEUE ---------------------
    def pop_page(self) -> Optional[str]:
        try :
            popped = self.client.brpop(INDEXER_QUEUE_KEY)
            if not popped:
                logger.warning(f'Could not fetch from message queue')
                return None

            _, page_id = popped
            return page_id
        except Exception as e:
            logger.error(f'Could not fetch from message queue: {e}')
            return None
    
    def peek_page(self) -> Optional[str]:
        try:
            peeked = self.client.lrange(INDEXER_QUEUE_KEY, -1, -1)
            if not peeked:
                logger.warning(f'Could not peek from message queue')
                return None
            
            page_id = peeked[0]
            logger.debug(f'Peeked from message queue: {page_id}')
            return page_id
        except Exception as e:
            logger.error(f'Could not peek from message queue: {e}')
            return None

    def get_queue_size(self) -> Optional[int]:
        if self.client is None:
            logger.error(f'Redis connection not initialized')
            return
        return self.client.llen(INDEXER_QUEUE_KEY)

    def signal_crawler(self) -> None:
        if self.client is None:
            logger.error(f'Redis connection not initialized')
            return
        # Signal the crawler that we can continue
        self.client.lpush(SIGNAL_QUEUE_KEY, RESUME_CRAWL)
    # --------------------- MESSAGE QUEUE ---------------------

    # --------------------- PAGE DATA ---------------------
    def get_page_data(self, key: str) -> Optional[Page]:
        if self.client is None:
            logger.error(f'Redis connection not initialized')
            return None

        try:
            page_hashed = self.client.hgetall(key)

            if not page_hashed:
                logger.warning(f'Page with key {key} not found in Redis')
                return None

            logger.info(f'Page with key {key} successfully fetched')
            return Page.from_hash(page_hashed)
        except Exception as e:
            logger.error(f'Unexpected error while fetching {key}: {e}')
            return None

    def delete_page_data(self, key:str) -> None:
        if self.client is None:
            logger.error(f'Redis connection not initialized')
            return

        res = self.client.delete(key)
        if res <= 0:
            logger.error(f'Could not remove {key} from Redis')
    # --------------------- PAGE DATA ---------------------

    # --------------------- PAGE IMAGES ---------------------
    def get_page_images(self, normalized_url: str) -> Optional[List[str]]:
        key = f'{PAGE_IMAGES_PREFIX}:{normalized_url}'
        page_images_urls = self.client.smembers(key)

        if not page_images_urls:
            return []

        return [url for url in page_images_urls]

    def delete_page_images(self, normalized_url:str) -> None:
        if self.client is None:
            logger.error(f'Redis connection not initialized')
            return

        key = f'{PAGE_IMAGES_PREFIX}:{normalized_url}'
        res = self.client.delete(key)
        if res <= 0:
            logger.error(f'Could not remove {key} from Redis')

    # --------------------- PAGE IMAGES ---------------------

    # --------------------- IMAGES ---------------------
    # Get image and delete it from Redis
    def pop_image(self, image_url: str) -> Optional[Image]:
        key = f'{IMAGE_PREFIX}:{image_url}'
        image_hashed = self.client.hgetall(key)

        if not image_hashed:
            logger.error(f'Could not fetch image {key} from Redis')
            return None

        res = self.client.delete(key)
        if res <= 0:
            logger.error(f'Could not remove {key} from Redis')

        return Image.from_hash(image_hashed, image_url)
    # --------------------- IMAGES ---------------------

    # --------------------- OUTLINKS ---------------------
    def get_outlinks(self, normalized_url: str) -> Optional[Outlinks]:
        if self.client is None:
            logger.error(f'Redis connection not initialized')
            return None

        key = f'{OUTLINKS_PREFIX}:{normalized_url}'
        res = self.client.smembers(key)
        if not res:
            logger.warning(f'No outlinks found for {key}')
            return None

        return Outlinks(_id=normalized_url, links=res)

    def delete_outlinks(self, normalized_url: str) -> None:
        if self.client is None:
            logger.error(f'Redis connection not initialized')
            return

        key = f'{OUTLINKS_PREFIX}:{normalized_url}'
        res = self.client.delete(key)
        if res <= 0:
            logger.error(f'Could not remove {key} from Redis')
    # --------------------- OUTLINKS ---------------------
