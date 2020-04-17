# Synapse transformation

[![Build Status](https://travis-ci.com/keboola/synapse-transformation.svg?branch=master)](https://travis-ci.com/keboola/synapse-transformation)

Application which runs [KBC](https://connection.keboola.com/) transformations in Azure Synapse DB.

## Development
 
Clone this repository and init the workspace with following command:

```sh
git clone https://github.com/keboola/synapse-transformation
cd synapse-transformation
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Create `.env` file with following contents
```env
SYNAPSE_SERVER=
SYNAPSE_PORT=
SYNAPSE_DATABASE=
SYNAPSE_UID=
SYNAPSE_PWD=
```

Run the test suite using this command:

```sh
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
