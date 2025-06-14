import logging
import time
from typing import Dict, List, Optional, Set

import pymongo
from pymongo import UpdateOne

from models.metadata import Metadata
from models.outlinks import Outlinks
from models.page import Page

# SETUP LOGGER
logger = logging.getLogger(__name__)
logging.basicConfig(
    level=logging.INFO, format="%(asctime)s - %(name)s - %(levelname)s - %(message)s"
)
logger = logging.getLogger(__name__)

# COLLECTIONS
WORDS_COLLECTION = "words"
METADATA_COLLECTION = "metadata"
OUTLINKS_COLLECTION = "outlinks"
DICTIONARY_COLLECTION = "dictionary"


class MongoClient:
    def __init__(
        self, host="localhost", port=27017, password="", db="test", username=""
    ):
        try:
            self.client = pymongo.MongoClient(
                f"mongodb://{username}:{password}@{host}:{port}/{db}?authSource=admin"
            )

            logger.info(
                f"mongodb://{username}:{password}@{host}:{port}/{db}?authSource=admin"
            )
            self.db = self.client[db]
            self.client.admin.command("ping")
            logger.info("Successfully connected to mongo!")

            logger.info("Creating indexes...")
            words = self.db[WORDS_COLLECTION]
            # Create a compound index to ensure uniqueness on word and url
            words.create_index([("word", 1), ("url", 1)], unique=True)
            # Create a compound index to easily sort by word and weight
            words.create_index([("word", 1), ("weight", -1)])

            # Create single field indexes
            words.create_index("word")
            words.create_index("url")
        except Exception as e:
            logger.error(f"Failed to connect to mongo: {e}")
            self.client = None

    def perform_batch_operations(
        self, operations: List[UpdateOne], collection_name: str
    ):
        if self.client is None:
            logger.error(f"Mongo connection not initialized")
            return None

        if not operations:
            logger.warning(f"No operations to perform")
            return None

        try:
            res = self.db[collection_name].bulk_write(operations, ordered=False)
            return res
        except Exception as e:
            logger.error(f"Error performing batch operations: {e}")
            return None

    # --------------------- WORDS ---------------------
    def create_words_entry_operation(self, word: str, url: str, tf: int) -> None:
        if self.client is None:
            logger.error(f"Mongo connection not initialized")
            return None

        # Write the new word entry to the new database
        return UpdateOne(
            {"word": word, "url": url},
            {
                "$set": {
                    "tf": tf,
                    "weight": 0,  # Initialize weight to 0, this will be updated later in the tf-idf service
                }
            },
            upsert=True,
        )

    def create_words_bulk(self, operations: List[UpdateOne]):
        if not operations:
            return
        return self.perform_batch_operations(operations, WORDS_COLLECTION)

    # --------------------- WORDS ---------------------

    # --------------------- METADATA ---------------------
    def get_metadata(self, normalized_url: str) -> Optional[Metadata]:
        if self.client is None:
            logger.error(f"Mongo connection not initialized")
            return None

        collection = self.db[METADATA_COLLECTION]
        result = collection.find_one(
            {"_id": normalized_url},
        )

        return Metadata.from_dict(result)

    def create_metadata_entry_operation(
        self, page_data: Page, html_data: Metadata, top_words: Dict[str, int]
    ) -> None:
        if self.client is None:
            logger.error(f"Mongo connection not initialized")
            return None

        # Create Metadata object
        normalized_url = page_data.normalized_url
        metadata = Metadata(
            _id=normalized_url,
            title=html_data["title"],
            description=html_data["description"],
            summary_text=html_data["summary_text"],
            last_crawled=page_data.last_crawled,
            keywords=top_words,
        )

        return UpdateOne(
            {"_id": normalized_url},
            {
                "$set": metadata.to_dict(),
            },
            upsert=True,
        )

    def create_metadata_bulk(self, operations: List[UpdateOne]):
        if not operations:
            return
        return self.perform_batch_operations(operations, METADATA_COLLECTION)

    # --------------------- METADATA ---------------------

    # --------------------- OUTLINKS ---------------------
    def create_outlinks_entry_operation(self, outlinks: Outlinks) -> None:
        if self.client is None:
            logger.error(f"Mongo connection not initialized")
            return None

        if not outlinks:
            logger.error(f"Outlinks is None")
            return

        return UpdateOne(
            {"_id": outlinks._id},
            {
                "$set": outlinks.to_dict(),
            },
            upsert=True,
        )

    def create_outlinks_bulk(self, operations: List[UpdateOne]):
        if not operations:
            return
        return self.perform_batch_operations(operations, OUTLINKS_COLLECTION)

    # --------------------- OUTLINKS ---------------------

    # --------------------- DICTIONARY ---------------------
    def add_words_to_dictionary(self, words: Set[str]) -> None:
        if self.client is None:
            logger.error(f"Mongo connection not initialized")
            return None

        operations = [
            UpdateOne(
                {"_id": word},
                {
                    "$set": {
                        "_id": word,
                    }
                },
                upsert=True,
            )
            for word in words
        ]

        if not operations:
            logger.warning(f"No operations to perform")
            return None

        return self.perform_batch_operations(operations, DICTIONARY_COLLECTION)

    # --------------------- DICTIONARY ---------------------
