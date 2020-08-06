
# Elgentos_LargeConfigProducts

This extension is built to work around the problems Magento 2 is causing when using configurable products with extremely large amounts of simple products associated to it. Magento 2 can handle up to around 3000 associated simple products pretty well. Above that, it becomes extremely slow and sometimes unusable (such as webserver timeouts).

## Example

![peek 2018-01-19 10-14](https://user-images.githubusercontent.com/431360/35143386-a84cbd2e-fd01-11e7-9245-f9005aba04ed.gif)

## Problems it tries to solve

The main problems are;
- In the frontend, Magento 2 loads all variations (associated simple products) in a giant JSON object and renders that into the DOM. This JSON object is 20 megabytes for 10k variations.
- In the backend, this JSON is also built and passed to a UI component wrapped in XML. PHP's xmllib is not able to append extremely large XML structures to an existing XML structure.

We have created workaround for both problems. In the frontend, we offload fetching the JSON blob through an AJAX request. The JSON itself can be pre-warmed using a console command and is stored in Redis. We chose Redis over the Magento cache system itself because Redis is not flushed when the entire Magento cache is flushed (for example, during deployment).

When the product page is loaded and there is no cache entry, it will create it then. This will of course take longer than pre-warming the cache entries.

In the backend [we use DOMDocument's and the `LIBXML_PARSEHUGE` constant](https://github.com/elgentos/LargeConfigProducts/blob/0.1.3/View/TemplateEngine/Xhtml/Template.php) to handle the extremely large XML structure.

This extension is free to use. If you find any bugs, please let us know. It has been tested on Magento 2.4.0 on PHP 7.4.

## Console command
This extension comes with a console command, `php bin/magento lcp:prewarm`. This console command pre-warms the JSON blobs so your customers don't have to wait for the cache to build up on the first hit on the product page.

The command has a few options;

`--products 123,456,789` - define for which product ID(s) you want to run the prewarmer

`--storecodes english,dutch,german` - define for which store code(s) you want to run the prewarmer

`--force true` - force the prewarmer to overwrite existing entries. Otherwise the prewarmer will skip product/storecode combinations that already have an entry.

## Magento 2.3.X / 2.4.X
Magento 2.3.0 includes integrated message queue management using RabbitMQ, this has replaced the renatocason/magento2-module-mq module used for dynamic pre warming of configurable products after saving.

To use RabbitMQ messaging with this module you will require a RabbitMQ server accessible by Magento.

https://devdocs.magento.com/guides/v2.3/install-gde/prereq/install-rabbitmq.html

Configure the server in your env.php file

    'queue' =>
      array (
        'amqp' =>
        array (
          'host' => 'magento2_rabbitmq_1',
          'port' => 5672,
          'user' => 'guest',
          'password' => 'guest',
          'virtualhost' => '/'
         ),
      ),

After installing the module run setup:upgrade to create the message queue. Confirm the message queue exists with

    bin/magento queue:consumers:list

Start the message queue consumer with

    bin/magento queue:consumers:start elgentos_magento_lcp_product_prewarm

To test dynamic updating of the cache edit and save a configurable product (parent or child)

You will see the prewarm process updating the cache

    Processing 67..
    Prewarming
    Prewarming MH01 for store en (1/2)
    Prewarming MH01 for store de (2/2)
    Done prewarming

You can run the consumer as a background process. You may need to manage the consumer with a supervisor process to ensure it remains running. Alternatively if you are using Docker the consumer can run as a standalone container set to always restart.

### Changelog
0.3.6
 - Magento 2.3.5 / 2.4.0 compatibility

0.3.5
 - Throttle consumer process to avoid replicating product prewarm

0.3.4
 - Compatibility with Magento 2.3.x using built in AQMP/RabbitMQ
   integration
 - Removed requirement for renatocason/magento2-module-mq
 - Updated swatch-renderer-mixin updateBaseImage
 - Disabled configurable-customer-data from requirejs orginally used to auto select first option, not working in 2.3.x
 - Added option to disable cache per customer group so that group 0 is always used. Use this if you do not have customer group pricing
