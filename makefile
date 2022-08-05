help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-10s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
##
##
## RESPECT DES NORMES PSR-2 / Symfony
##
##
format: ## Format les fichiers à la norme PSR-2 / Symfony
	vendor/bin/php-cs-fixer fix --verbose

formattest: ## Exécute le test de format comme les build gitlab
	vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

analyse: ## Exécute phpstan
	vendor/bin/phpstan analyse src --level 1

unittest: ## Lance les tests unitaires
	vendor/bin/simple-phpunit

browserdrivers: ## Installation des drivers chrome et gecko pour Panther
	vendor/bin/bdi detect drivers

composerinstall: ## Lit le fichier composer.json, resout les dépendances et les installe dans le dossier "vendor"
	composer install -n;

schema: ## Mise à jour de la structure BDD
	bin/console d:s:u --force

install: composerinstall schema #install avec gestion des assets webpack

