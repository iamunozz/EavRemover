# Module EavRemover

### Installation
#### Developer mode
```sh
$ php bin/magento module:enable Iamunozz_EavRemover --clear-static-content
$ php bin/magento setup:upgrade
$ php bin/magento setup:di:compile
$ php bin/magento setup:static-content:deploy -f
```

#### Production mode
```sh
$ php bin/magento module:enable Iamunozz_EavRemover --clear-static-content
$ php bin/magento setup:upgrade
$ php bin/magento setup:di:compile
$ php bin/magento setup:static-content:deploy
```

### Configurations
N/A
### Usage
Run `bin/magento` in the Magento 2 root and look for the `eav:` commands.

### Commands
* `eav:remover` Interactive mode to delete a eav attribute.

### Attribute Type
* `eav:remover --type` Attribute type to be removed, can be type of 'customer' or 'catalog_product'.

### Attribute Code
* `eav:remover --type` Attribute code to be removed.

### Help
```sh
$ php bin/magento eav:remover --help
```
