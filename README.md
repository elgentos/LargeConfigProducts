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

This extension is free to use. If you find any bugs, please let us know. It has been tested on Magento 2.2.2 on PHP 7.0.