# brainSKwiz - Interactive Quiz Web Application

Welcome to **brainSKwiz**, a modern interactive quiz web application. This repository bundles a fully containerized stack using Docker and Docker Compose, achieving a production-ready infrastructure development setup with automatic local data seeding.

---

## Project Overview

`brainSKwiz` is a web-based quiz platform designed to test users' knowledge and tracking their performance. 

### Key Features

* **General Knowledge Quiz Engine:** An interactive quiz system featuring various general knowledge questions. 
  * **Time Constraint:** Players face a strict **15-second countdown** per question to boost engagement.
  * **Real-Time Feedback:** Live tracking of the total current score and instant display of mistakes made during the active session.
* **History & Global Leaderboard:** 
  * **Match History:** A detailed log of past games for users to review their previous performances.
  * **Global Ranking:** A dynamic leaderboard showcasing top players to foster competition.
* **User Authentication & Security:** Secure login, session management, and robust client/server-side password validation rules.
* **Dynamic Account Settings:** Custom profile management allowing users to update their usernames, change passwords (with dynamic visibility toggle), and select custom pre-loaded avatars.
* **Interactive User Dashboard:** Visual presentation of user statistics (best time, average response time, success rate, and global ranking).
* **Comprehensive Admin Panel:** A dedicated dashboard for administrators to asynchronously manage the entire platform ecosystem: view, create, edit, and delete **quizzes, question categories, questions**, and **user accounts**.

---

## Tech Stack

* **Back-End:** PHP 8.2 (Native, OOP concepts, PDO for secure database interactions).
* **Database:** MySQL 8.0 (Relational schema with foreign key constraints).
* **Front-End:** Vanilla JavaScript (ES6+, asynchronous operations via Native Fetch API & JSON payloads), HTML5, Tailwind CSS.
* **Icons:** Google Material Icons.
* **DevOps:** Docker & Docker Compose.

---

## System Architecture

The layout maps the connection between your machine, the HTTP Web Server, and the isolated Back-End Database engine.

[ Your Browser ] --- (Port 8080) ---> [ WEB Container ] ---> [ DB Container ]
(http://localhost:8080)               (Apache + PHP)         (MySQL 8.0)
|                                     |                      |
|<--- Returns HTML/Tailwind <---------|<-- Returns DB data --|

* **Front-End Integration:** Responsive layout handled via Tailwind CSS utility classes.
* **Asynchronous Updates:** Client-side components communicate with the PHP backend API endpoints using structured JSON payloads via the `Fetch API`, preventing unnecessary page reloads.

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
> docker-compose up --build

Open your browser and navigate to: http://localhost:8080

### Data Management (Persistence)

To close the application while preserving your changes :
> docker-compose down

To completely reset the application and reload the initial test data :
> docker-compose down -v && docker-compose up -d

### Seeded Database Structure & Sample Data

The automatic initialization script injects a complete relational schema into your MySQL instance, pre-loaded with:

* **Categories & Quizzes:** Pre-defined topics (e.g., Science, History, Pop Culture) with structured quizzes.
* **Questions & Answers:** Multiple-choice questions mapped to a 15-second timer format with explicit correct/incorrect flags.
* **Users & Roles:** Separate user profiles (Admin/Standard) to test different access control layers right out of the box.
* **Pre-linked History:** Sample game records to instantly populate the Leaderboard and History tabs upon your first login.