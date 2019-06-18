# Kalamu / Dynamique Config Bundle

This bundle provide a way to define some configuration parameters that are not
dependants of the symfony container. As they are independants, they can be
changed without interfering with Symfony configuration and without clearing the
cache.

This bundle use a parameter container that is persisted in the file
`app/config/dynamique_config.yml`

## Usage

``` php
$config = $container->get('kalamu_dynamique_config');
echo "The background color is : ".$config->get('background_color', 'red'); // get the value of 'background_color' or 'red' if it's not defined
$config->set('background_color', 'blue'); // change the configuration
$config->has('background_color'); // return TRUE
$config->remove('background_color'); // remove this parameter
```


