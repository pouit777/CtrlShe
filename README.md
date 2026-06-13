# brainSKwiz - Docker Development Environment

Welcome to **brainSKwiz**, a modern interactive quiz web application. This repository bundles a fully containerized stack using Docker and Docker Compose, achieving a production-ready infrastructure development setup with automatic local data seeding.

---

## System Architecture

The layout maps the connection between your machine, the HTTP Web Server, and the isolated Back-End Database engine.

[ Your Browser ] --- (Port 8080) ---> [ WEB Container ] ---> [ DB Container ]
(http://localhost:8080)               (Apache + PHP)         (MySQL 8.0)
|                                     |                      |
|<--- Returns HTML/Tailwind <---------|<-- Returns DB data --|

* **Front-End Integration:** Tailwind CSS built via CDN handling adaptive UI states.
* **Asynchronous Updates:** Client-side interactions communicate via Native Fetch API using structured JSON representations payloads.

---

## Component Breakdown

The orchestration relies on two centralized container images definition configurations:

### 1. `Dockerfile`
Builds upon an official **PHP 8.2 Apache** image framework. It comes packaged with internal utilities required to scale relational structures:
* Enables `pdo` & `pdo_mysql` PHP extensions to natively manage statements.
* Activates Apache's core `mod_rewrite` engine module for future custom MVC pretty routing definitions.
* Enforces secure system permissions adjustments over `/var/www/html` bounding context data safely to the `www-data` group layer.

### 2. `docker-compose.yml`
Handles infrastructure bindings over an isolated **Bridge Network** stack:
* **`web` service:** Mounts your `./src` repository folder directly as a live bind-mount volume into `/var/www/html` to monitor alterations in real-time without reloading containers. Maps port `8080` externally.
* **`db` service:** Operates a detached instance running **MySQL 8.0** mapping port `3306`. It binds a persistent volume handle named `db_data` to ensure entries are safely written to disk across reboots.

---

## Getting Started

### Prerequisites
Make sure you have [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and active on your system.

### Quick Launch

1. Open your terminal at the root directory of the project (where `docker-compose.yml` resides).
2. Fire up the configuration stack by executing the automatic build routine:
>> docker-compose up --build