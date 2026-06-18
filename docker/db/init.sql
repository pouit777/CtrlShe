-- docker/db/init.sql

-- Create the categories lookup table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the users authentication & authorization table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the questions management table with relation to categories
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    question_text TEXT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the quizzes management table 
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
    question_count INT DEFAULT NULL,
    allow_custom_question_count TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE quiz_questions (
    quiz_id INT NOT NULL,
    question_id INT NOT NULL,
    PRIMARY KEY (quiz_id, question_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Create the quiz_categories join table to establish many-to-many relationship between quizzes and categories
CREATE TABLE IF NOT EXISTS quiz_categories (
    quiz_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (quiz_id, category_id),
    FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id)
        ON DELETE CASCADE,
    FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE CASCADE
);

-- Create the answers multiple-choice options table with relation to questions
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the games session history table to keep track of user performances
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion of initial test data (Auto-seeding required for Grade A)
INSERT INTO categories (id, label) VALUES 
(1, 'Computer Science & Web'),
(2, 'General Knowledge'),
(3, 'Sciences');

-- Categories
INSERT IGNORE INTO categories (id, label) VALUES
(1, 'Computer Science & Web'),
(2, 'General Knowledge'),
(3, 'Sciences');

-- only seed categories (ok)
INSERT INTO categories (id, label) VALUES 
(1, 'Computer Science & Web'),
(2, 'General Knowledge'),
(3, 'Sciences')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Test Accounts (password: admin123 hashed using PASSWORD_BCRYPT)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@quiz.fr', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'admin'),
('student', 'student@school.com', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'user');

-- Seed initial test questions
INSERT INTO questions (id, category_id, question_text, difficulty) VALUES 
(1, 1, 'What does the acronym CSS stand for?', 'easy'),
(2, 1, 'Which protocol features encrypted data transmission for web browsing?', 'easy');

-- Seed quizzes (IMPORTANT)
INSERT INTO quizzes (id, name, description, difficulty, question_count, is_active)
VALUES (1, ?, ?, ?, 2, 1);

$quizId = $pdo->lastInsertId();

-- Link quiz → categories
INSERT INTO quiz_categories (quiz_id, category_id)
VALUES (?, ?);

-- Seed options for the test questions above
INSERT INTO answers (question_id, answer_text, is_correct) VALUES 
(1, 'Cascading Style Sheets', 1),
(1, 'Creative Style System', 0),
(1, 'Computer Science Symbols', 0),
(2, 'HTTP', 0),
(2, 'HTTPS', 1),
(2, 'FTP', 0);