![NumberNine Logo](./assets/images/NumberNine512_slogan.png)

# Introduction to NumberNine Webhooks

This application needs to be installed on the deployment server.

It exposes an API that Travis can use to trigger a deployment with 
[webhook notifications](https://docs.travis-ci.com/user/notifications/#configuring-webhook-notifications).

# Usage

Setup two environment variables `DOCKER_IMAGE` and `DOCKER_DESTINATION_VOLUME`, as an example:

```dotenv
DOCKER_IMAGE=myorganization/my-app:latest
DOCKER_DESTINATION_VOLUME=my_app_volume
```

**Important considerations**

* The image referenced by `DOCKER_IMAGE` needs to be built with the NumberNine application builder
* All data present in the volume referenced by `DOCKER_DESTINATION_VOLUME` will be replaced with the new image files.
**Never create user content files in this volume!**

# License
[MIT license](./LICENSE)

Copyright (c) 2020, William Arin
