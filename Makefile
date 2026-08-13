.PHONY: up down logs reset

SHOPWARE_PORT ?= 8080
# First boot pulls the image and runs plugin install inside boot_end.sh.
WAIT_TIMEOUT ?= 300

up:
	docker compose up -d --wait --wait-timeout $(WAIT_TIMEOUT)
	@echo ""
	@echo "Dropday Shopware demo is ready."
	@echo "  Storefront: http://localhost:$(SHOPWARE_PORT)"
	@echo "  Admin:      http://localhost:$(SHOPWARE_PORT)/admin  (admin / shopware)"
	@echo "  Adminer:    http://localhost:8888"
	@echo ""

down:
	docker compose down

logs:
	docker compose logs -f shop

reset:
	docker compose down -v
	$(MAKE) up
