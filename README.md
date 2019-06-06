# Plinth

Plinth is a small open source PHP Framework.

At the moment there's no documentation present, but I'll try to add it as soon as possible.

## How to use

### Composer

You need to add Plinth to your composer.json.

	{
	    "require": {
	        "plinth/plinth": "~0.2"
	    },
	    "scripts" : {
	    	"post-install-cmd" : "PlinthScripts\\ComposerHandler::initProject"
	    }
	}
	
Afterwards if you want to create a new Plinth project run:

	php composer.phar install
	
Plinth will create the necessary project files to start your web app.

## Requirements

* PHP v5.5.0+

## Contributing

If you want to contribute code please fork this repository and create a pull request to merge your changes.
Your changes will be reviewed and merged afterwards if approved by the repository maintainers.

## License

MIT License

* License: [MIT](LICENSE)