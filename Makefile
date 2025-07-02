# Coupon Manager for GravityForms - Makefile
# Provides convenient commands for building and managing the plugin

.PHONY: build clean test help install-deps lint

# Default target
.DEFAULT_GOAL := help

# Plugin configuration
PLUGIN_NAME = coupon-manager
BUILD_DIR = build
VERSION := $(shell grep "Version:" gf-coupon-generator.php | sed 's/.*Version: \([0-9.]*\).*/\1/')

# Colors for output
BLUE = \033[0;34m
GREEN = \033[0;32m
YELLOW = \033[1;33m
NC = \033[0m

help: ## Show this help message
	@echo "$(BLUE)Coupon Manager for GravityForms - Available Commands$(NC)"
	@echo "================================================="
	@echo ""
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "$(GREEN)%-15s$(NC) %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build production-ready plugin zip file
	@echo "$(BLUE)Building plugin v$(VERSION)...$(NC)"
	@./build-plugin.sh

clean: ## Clean build directory and temporary files
	@echo "$(BLUE)Cleaning build files...$(NC)"
	@rm -rf $(BUILD_DIR)
	@rm -rf vendor
	@rm -rf coverage
	@find . -name "*.log" -delete 2>/dev/null || true
	@find . -name ".DS_Store" -delete 2>/dev/null || true
	@find . -name "Thumbs.db" -delete 2>/dev/null || true
	@echo "$(GREEN)Clean completed$(NC)"

install-deps: ## Install development dependencies
	@echo "$(BLUE)Installing development dependencies...$(NC)"
	@composer install
	@echo "$(GREEN)Dependencies installed$(NC)"

test: ## Run all tests
	@echo "$(BLUE)Running tests...$(NC)"
	@composer run-script test

test-unit: ## Run unit tests only
	@echo "$(BLUE)Running unit tests...$(NC)"
	@composer run-script test:unit

test-integration: ## Run integration tests only
	@echo "$(BLUE)Running integration tests...$(NC)"
	@composer run-script test:integration

test-database: ## Run database tests only
	@echo "$(BLUE)Running database tests...$(NC)"
	@composer run-script test:database

lint: ## Run PHP CodeSniffer
	@echo "$(BLUE)Running PHP CodeSniffer...$(NC)"
	@vendor/bin/phpcs

lint-fix: ## Fix PHP CodeSniffer issues automatically
	@echo "$(BLUE)Fixing PHP CodeSniffer issues...$(NC)"
	@vendor/bin/phpcbf

coverage: ## Generate test coverage report
	@echo "$(BLUE)Generating coverage report...$(NC)"
	@composer run-script test:coverage
	@echo "$(GREEN)Coverage report generated in coverage/$(NC)"

quick-build: ## Quick build without validation (faster)
	@echo "$(BLUE)Quick building plugin...$(NC)"
	@rm -rf $(BUILD_DIR)
	@mkdir -p $(BUILD_DIR)/$(PLUGIN_NAME)
	@cp gf-coupon-generator.php $(BUILD_DIR)/$(PLUGIN_NAME)/
	@cp README.md $(BUILD_DIR)/$(PLUGIN_NAME)/
	@cp -r assets $(BUILD_DIR)/$(PLUGIN_NAME)/ 2>/dev/null || true
	@cp -r views $(BUILD_DIR)/$(PLUGIN_NAME)/ 2>/dev/null || true
	@cp composer.json $(BUILD_DIR)/$(PLUGIN_NAME)/ 2>/dev/null || true
	@cd $(BUILD_DIR) && zip -r $(PLUGIN_NAME)-v$(VERSION).zip $(PLUGIN_NAME)/ >/dev/null
	@echo "$(GREEN)Quick build completed: $(BUILD_DIR)/$(PLUGIN_NAME)-v$(VERSION).zip$(NC)"

install-wp-tests: ## Install WordPress test suite
	@echo "$(BLUE)Installing WordPress test suite...$(NC)"
	@bash tests/bin/install-wp-tests.sh wordpress_test root root localhost latest

dev-setup: install-deps install-wp-tests ## Complete development environment setup
	@echo "$(GREEN)Development environment setup completed$(NC)"

release: clean build ## Clean and build for release
	@echo "$(GREEN)Release build completed for v$(VERSION)$(NC)"

version: ## Show current plugin version
	@echo "$(BLUE)Current version: $(GREEN)$(VERSION)$(NC)"

size: ## Show build size information
	@if [ -f "$(BUILD_DIR)/$(PLUGIN_NAME)-v$(VERSION).zip" ]; then \
		echo "$(BLUE)Build size information:$(NC)"; \
		ls -lh $(BUILD_DIR)/$(PLUGIN_NAME)-v$(VERSION).zip | awk '{print "Zip file: " $$5}'; \
		unzip -l $(BUILD_DIR)/$(PLUGIN_NAME)-v$(VERSION).zip | tail -1 | awk '{print "Uncompressed: " $$1 " bytes"}'; \
	else \
		echo "$(YELLOW)No build found. Run 'make build' first.$(NC)"; \
	fi

check: ## Check plugin files and structure
	@echo "$(BLUE)Checking plugin structure...$(NC)"
	@echo "Main plugin file: $$(test -f gf-coupon-generator.php && echo '✅' || echo '❌')"
	@echo "Assets directory: $$(test -d assets && echo '✅' || echo '❌')"
	@echo "Views directory: $$(test -d views && echo '✅' || echo '❌')"
	@echo "Tests directory: $$(test -d tests && echo '✅' || echo '❌')"
	@echo "Composer file: $$(test -f composer.json && echo '✅' || echo '❌')"
	@echo "README file: $$(test -f README.md && echo '✅' || echo '❌')" 