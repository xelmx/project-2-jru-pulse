# project-2-jru-pulse
JRU-PULSE: Performance and User-satisfaction Linked Service Evaluation for Jose Rizal University

**JRU-PULSE** is a comprehensive, web-based survey platform developed as an IT Capstone Project. It is designed to centralize and streamline the process of creating, deploying, and analyzing feedback surveys within José Rizal University. The platform's core feature is an intelligent analytics dashboard that provides actionable insights from respondent feedback using sentiment analysis and keyword extraction.

## Project Goal

The primary objective of JRU-PULSE is to replace a fragmented and manual survey system with a unified, data-driven platform. This system empowers administrators to manage the entire survey lifecycle while ensuring the long-term integrity of collected data for robust analysis. The final deliverable is an intelligent dashboard that moves beyond simple data visualization to provide deep insights into respondent sentiment and common concerns.

## Core Features

- **Full Survey Lifecycle Management:** Administrators can create, save as draft, publish, deactivate, and archive surveys.
- **Dynamic Survey Builder:** An intuitive interface for building surveys with various question types (Likert scales, ratings, text areas).
- **Secure Admin Panel:** Role-based access control ensures that only authorized personnel can manage surveys and view analytics. *(Features Office Head and Admin roles).*
- **Unified Respondent Management:** Separate, dedicated modules for managing official Student records and external Guest respondents, ensuring data integrity.
- **CSV Data Import:** A robust "Smart Import" feature for bulk-adding student records, which intelligently handles flexible column ordering.
- **Intelligent Analytics Dashboard:**
    - **Sentiment Analysis:** Automatically analyzes text feedback to classify comments as Positive, Neutral, or Negative.
    - **Common Feedback Extraction:** Uses keyword extraction to identify the most frequently discussed topics in open-ended feedback.
    - **Dynamic Data Visualization:** All metrics and charts are dynamically updated based on user-selected date ranges.

## System Architecture

JRU-PULSE utilizes a **microservice-oriented architecture** to separate the main web application from the computationally intensive AI/ML tasks.

1.  **Main Web Application (PHP):**
    - A traditional LAMP stack application (hosted on XAMPP for local development) that handles all core functionality: user authentication, survey management, data storage, and the user interface.
    - It serves as the primary interface for both administrators and survey respondents.

2.  **AI/NLP Service (Python):**
    - A separate, standalone web API built with Python and FastAPI.
    - This service is responsible for all Natural Language Processing tasks. It loads a custom-trained machine learning model into memory and exposes endpoints for sentiment analysis and keyword extraction.
    - The PHP application communicates with this service via HTTP requests to offload the heavy AI processing.

This separation ensures that the main application remains fast and responsive, while the specialized AI tasks are handled by the environment best suited for them.

## Technology Stack

- **Back-End:** PHP 8.2, MySQL (via MariaDB on XAMPP)
- **Front-End:** HTML5, CSS3, Tailwind CSS, JavaScript (ES6+)
- **JavaScript Libraries:** Chart.js for data visualization.
- **AI / Machine Learning:**
    - **Framework:** Python 3.11+, FastAPI
    - **Core Libraries:** PyTorch, Transformers, KeyBERT, Scikit-learn
    - **Models:** `cardiffnlp/twitter-xlm-roberta-base-sentiment` (Sentiment), `paraphrase-multilingual-MiniLM-L12-v2` (Keywords), and a custom-trained XLM-RoBERTa regression model for predictive analytics.

## Local Development Setup

To run the full JRU-PULSE system locally, both the PHP web application and the Python AI service must be running.

### 1. PHP Web Application Setup

1.  Ensure you have **XAMPP** installed with Apache and MySQL services running.
2.  Clone this repository into your `htdocs` directory (e.g., `D:\xampp\htdocs\jru-pulse`).
3.  Import the `jru_pulse.sql` database schema into your phpMyAdmin.
4.  Configure the database credentials by creating a `.env` file in the project root. A `.env.example` file is provided as a template.

### 2. Python AI Service Setup

The AI service must be run separately in a command prompt or terminal.

1.  **Prerequisites:** Python 3.10+ must be installed. Remember to check "Add Python to PATH" during installation.
2.  **Generate the Model:**
    - Open the Jupyter Notebook `[Your Notebook File Name].ipynb` in Google Colab.
    - Set the runtime to `T4 GPU`.
    - Run the notebook cells in order, uploading the `[Your CSV Data File].csv` when prompted.
    - After the training cell completes, download the generated `xlmr_satisfaction_regressor` folder.
3.  **Set Up the Local Server:**
    - Create a separate project folder (e.g., `C:\jru-pulse-api`).
    - Place the downloaded `xlmr_satisfaction_regressor` folder and the `main.py` script (from the notebook) inside this new directory.
    - Navigate to this directory in a command prompt and set up the virtual environment:
      ```bash
      python -m venv venv
      venv\Scripts\activate
      ```
    - Install the required packages:
      ```bash
      pip install fastapi "uvicorn[standard]" torch transformers keybert sentence-transformers pydantic scikit-learn tiktoken protobuf sentencepiece
      ```
    - Run the server. **This terminal window must be left open while using the application.**
      ```bash
      uvicorn main:app --host 0.0.0.0 --port 8000
      ```

## Project Team

- **Lyle** - [Project Lead, Full-Stack Developer]
- **Isaac** - [AI/ML Developer]
- **Migs** - [Full stack Developer]
- **Rette** - [Analyst, Technical Writer]