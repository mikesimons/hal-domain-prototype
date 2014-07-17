#!/bin/sh
curl -vvv -XPUT 'http://127.0.0.1:9200/products/product/1' -d '{ "sku": "MSP001", "name": "Lovely socks", "description": "Some lovely woolen socks", "care_instruction_id": 1 }'
curl -vvv -XPUT 'http://127.0.0.1:9200/products/product/2' -d '{ "sku": "MSP002", "name": "Lovely sweater", "description": "A lovely woolen sweater", "attribute_ids": [ 1, 2 ] }'
curl -vvv -XPUT 'http://127.0.0.1:9200/product_images/product_image/1' -d '{ "id": 1, "sku": "MSP001", "href": "http://example.org/media/MSP001.jpg" }'
curl -vvv -XPUT 'http://127.0.0.1:9200/care_instructions/care_instruction/1' -d '{ "id": 1, "text": "Jiggery pokery" }'
curl -vvv -XPUT 'http://127.0.0.1:9200/care_instructions/care_instruction/2' -d '{ "id": 2, "text": "Spangle" }'
curl -vvv -XPUT 'http://127.0.0.1:9200/attributes/attribute/1' -d '{ "id": 1, "slug": "essence-of-chuck-norris", "invincible": true }'
curl -vvv -XPUT 'http://127.0.0.1:9200/attributes/attribute/2' -d '{ "id": 2, "slug": "water-repelleant", "like-a-duck": true }'

