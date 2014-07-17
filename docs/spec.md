# HAL mapper
The idea behind this project would be to implement a solution that takes one or more domain objects and produces a full HAL representation with links and embedded data based on domain object type and a mapping definition.

## Rel spec

As an example, consider the following:

### Product
- URL pattern is /products/:product_sku
- has images relation (collection rel)
- has care instruction relation (singular rel)
- has attribute collection relation (collection rel)

### ProductCollection
- URL pattern is /products
- has pagination
- is collection of Product entities

### ProductImage
- URL pattern is /products/:product_sku/images/:product_image_id
- has product relation (singular rel)

### ProductImageCollection
- URL pattern is /products/:product_sku/images
- has product relation (singular rel)

### CareInstructions
- Is a shared resource (multiple products may have the same instructions)
- URL pattern is /care/:care_instruction_id

### AttributeCollection
- Is a shared resource (multiple products may have the same attribute collection)
- URL pattern is /attributes

### Attribute
- URL pattern is /attributes/:attribute_id

This scenario covers all edge cases I can recall from prior work which are:

- Exclusive collection relations (Product -> ProductImageCollection) (One to many)
- Shared collection relations (Product -> AttributeCollection) (Many to many)
- Exclusive singuar relation (ProducImage -> Product) (One to one)
- Shared singular relation (Product -> CareInstructions) (Many to one)
- Cyclic relations (Product -> ProductImage -> Product …)
- No relations (Attribute)

- !! Missing collection relations

## Example request

Given the above definitions a request to http://example.org/products?zoom=product.images,attributes might produce the following output:

{
    "_embedded": {
        "products": [
            {
                "_embedded": {
                    "attributes": [
                        {
                            "_links": {
                                "self": {
                                    "href": "/attribute/water-repellant"
                                }
                            },
                            "like-a-duck": true
                        },
                        {
                            "_links": {
                                "self": {
                                    "href": "/attribute/essence-of-chuck-norris"
                                }
                            },
                            "invicible": true
                        }
                    ],
                    "images": [
                        {
                            "_links": {
                                "product": {
                                    "href": "/products/MSP001"
                                },
                                "self": {
                                    "href": "/products/MSP001/images/1"
                                }
                            },
                            "href": "http://example.org/images/MSP001.png"
                        }
                    ]
                },
                "_links": {
                    "care_instructions": {
                        "href": "/care/wood"
                    },
                    "self": {
                        "href": "/products/MSP001"
                    }
                },
                "description": "Some super spangleh stuff",
                "name": "Spangleh stuff",
                "sku": "MSP001"
            },
            {
                "_embedded": {
                    "attributes": [
                        {
                            "_links": {
                                "self": {
                                    "href": "/attribute/essence-of-chuck-norris"
                                }
                            },
                            "invicible": true
                        }
                    ]
                },
                "_links": {
                    "self": {
                        "href": "/products/MSP002"
                    }
                },
                "description": "Some super spangleh tings",
                "name": "Spangleh tings",
                "sku": "MSP002"
            }
        ]
    },
    "_links": {
        "next": {
            "href": "/products?zoom=product.images,attributes&page=2"
        },
        "self": {
            "href": "/products?zoom=product.images,attributes"
        }
    }
}

## Idea#1: JIT style

- Each domain object type requires unique entity and collection classes
- Per class configuration defines url, attribute extraction and relations
- Relation mapper must be able to hook in to data access layer to request embedded resources
- Generic mapper class iterates domain objects, processing zoom to produce a hal tree
- Hal tree is built as entities, collections and relations are iterated

## Idea#2: AOT style

- Entity tree must be built in advance
