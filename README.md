# aliyun-oss-cdn

Wordpress plugin which uploads files to Aliyun OSS and use CDN to cache static resource in China.

## Getting Started

These instructions will get you a copy of the project up and running on your wordpress for development or production.

### Prerequisites

What things you need to install the software and how to install them

```
php-curl
```

### Installing

Simple upload this plugin to wordpress and active it.

Fill the information from Aliyun OSS/CDN.

## Running the tests

The plugin will execute hourly, and upload 50 items each time.
If you want test it, you can use the purge function input the resource like:
```
wp-content/test.js
```
Then check is this file exist on your Aliyun OSS.


## Deployment

Too less resource working on wordpress intergrate with Chinese cloud provider, welcome to any suggestions.

## Built With

* [Aliyun OSS PHP SDK](https://github.com/aliyun/aliyun-oss-php-sdk) - Used on upload class


## Authors

* **Ruo D.** - *Initial work* - (https://github.com/ruodeng)

See also the list of [contributors](https://github.com/your/project/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Thanks to

* [时尚宝典](https://shishangbaodian.com) 
