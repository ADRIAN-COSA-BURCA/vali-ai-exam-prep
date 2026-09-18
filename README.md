# AI-Enhanced Exam Preparation Web Platform

A modular, cross-browser, and responsive study platform containerized using Docker.

## Tech Stack
- **Frontend**: HTML5, CSS3 (Single unified stylesheet), Vanilla JavaScript (Interactivity & Chart.js)
- **Backend**: PHP 8.1 (Modular MVC structure, all files kept under 120 lines)
- **Database**: MySQL 8.0 (Pre-seeded with 40+ exams from all academic/vocational domains)
- **AI Processing**: Python Azure Function container (Supports Azure AI Search, Azure OpenAI, and an offline Mock Mode)

## Setup & Execution

1. Make sure you have Docker and Docker Compose installed.
2. Open a terminal in the project directory and run:
   ```bash
   docker compose up --build
   ```
3. Access the services in your browser:
   - **Exam Preparation Web App**: [http://localhost:8080](http://localhost:8080)
   - **phpMyAdmin (DB Utility)**: [http://localhost:8081](http://localhost:8081)

## Default Administrator Account
- **Admin Email**: `admin@examprep.com`
- **Admin Password**: Set the `ADMIN_PASSWORD` value in your local `.env` file.
