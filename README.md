# Gravity Forms Cloud Storage

A custom [Gravity Forms Feed AddOn](https://docs.gravityforms.com/gffeedaddon/) allowing Gravity Forms entries
to be stored to the cloud.  This allows access to form entries without having to provide access to the website.

## Features

Gravity Forms Cloud Storage started with NextCloud, but with the plan to scale to other cloud providers.

- Specify cloud credentials (basic auth)
- Specify dynamic folder(s) hierarchy using merge tags
- Specify dynamic file name using merge tags

## Roadmap

- Namespaces with PSR-4 Autoloading
- PDF Generation of HTML Templates
  - Detection and Embedding of Uploaded Assets by File Type
- Entry Templates
- Extended CloudProvider Classes
  - Google Drive
    - Settings Field: API Key
    - Settings Field: Google Service Account
  - One Drive
  - AWS S3 Buckets
