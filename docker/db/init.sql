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
    avatar VARCHAR(100) NOT NULL DEFAULT 'bee.png',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quiz_questions (
    quiz_id INT NOT NULL,
    question_id INT NOT NULL,
    PRIMARY KEY (quiz_id, question_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    quiz_id INT NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    duration INT NOT NULL DEFAULT 0
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE game_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_id INT,
    is_correct TINYINT(1) NOT NULL,

    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE SET NULL
);

-- =========================================================================
-- SEEDING DATA
-- =========================================================================

-- Seed initial categories question
INSERT INTO categories (id, label) VALUES 
(1, 'Computer Science & Web'),
(2, 'General Knowledge'),
(3, 'Sciences'),
(4, 'History & Geography'),
(5, 'Pop Culture & Gaming')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Test Accounts (password: admin123 hashed using PASSWORD_BCRYPT)
-- INSERT INTO users (username, email, password, role) VALUES 
-- ('admin', 'admin@quiz.fr', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'admin'),
-- ('student', 'student@school.com', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'user');
INSERT INTO users (id, username, email, password, role, avatar) VALUES 
(1, 'Admin', 'admin@quiz.fr', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'admin', 'racoon.png'),
(2, 'student', 'student@school.com', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'user', 'bee.png'),
(3, 'player_one', 'player1@gmail.com', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'user', 'monkey.png'),
(4, 'quizmaster', 'master@brain.com', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'admin', 'lion.png')
ON DUPLICATE KEY UPDATE username=VALUES(username);

-- Seed initial test questions
INSERT INTO questions (id, category_id, question_text, difficulty) VALUES 
-- Category 1 : Web
(1, 1, 'What does the acronym CSS stand for?', 'easy'),
(2, 1, 'Which protocol features encrypted data transmission for web browsing?', 'easy'),
(3, 1, 'Which programming language is mainly executed client-side in browsers?', 'medium'),
-- Category 3 : Sciences
(4, 3, 'What is the approximate speed of light?', 'hard'),
(5, 3, 'Which planet in our solar system is known for its prominent ring system?', 'easy'),
-- Category 4 : History / Geo
(6, 4, 'In which year did the French Revolution begin?', 'medium'),
(7, 4, 'What is the capital city of Australia?', 'medium')
ON DUPLICATE KEY UPDATE question_text=VALUES(question_text);

-- Seed options for the test questions above
INSERT INTO answers (id, question_id, answer_text, is_correct) VALUES 
-- Q1 (CSS)
(1, 1, 'Cascading Style Sheets', 1),
(2, 1, 'Creative Style System', 0),
-- Q2 (HTTPS)
(3, 2, 'HTTP', 0),
(4, 2, 'HTTPS', 1),
-- Q3 (JS)
(5, 3, 'PHP', 0),
(6, 3, 'JavaScript', 1),
-- Q4 (Vitesse lumière)
(7, 4, '300,000 km/s', 1),
(8, 4, '150,000 km/s', 0),
-- Q5 (Saturne)
(9, 5, 'Mars', 0),
(10, 5, 'Saturn', 1),
-- Q6 (Révolution Française)
(11, 6, '1789', 1),
(12, 6, '1799', 0),
-- Q7 (Canberra)
(13, 7, 'Sydney', 0),
(14, 7, 'Canberra', 1)
ON DUPLICATE KEY UPDATE answer_text=VALUES(answer_text);

-- Seed quizzes 
INSERT INTO quizzes (id, name, description, difficulty, question_count, is_active) VALUES 
(1, 'Mastering the Web', 'Test your fundamental skills in modern web development, stylesheets, and browser environments.', 'medium', 3, 1),
(2, 'Cosmos & History Chronicles', 'An advanced journey combining deep space physics, planetary sciences, and major historical milestones.', 'hard', 3, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

-- Liaisons : Quizzes <-> Categories (quiz_categories)
INSERT INTO quiz_categories (quiz_id, category_id) VALUES 
-- Quiz 1 possède EXACTEMENT 1 catégorie : Computer Science & Web (id: 1)
(1, 1), 

-- Quiz 2 possède EXACTEMENT 2 catégories : Sciences (id: 3) ET History & Geography (id: 4)
(2, 3), 
(2, 4)
ON DUPLICATE KEY UPDATE quiz_id=VALUES(quiz_id);

-- Liaisons : Quizzes <-> Questions (quiz_questions)
INSERT INTO quiz_questions (quiz_id, question_id) VALUES 
-- Association pour le Quiz 1 (Web)
(1, 1), 
(1, 2), 
(1, 3), 

-- Association pour le Quiz 2 (Sciences + Histoire)
(2, 4), 
(2, 5), 
(2, 6)
ON DUPLICATE KEY UPDATE quiz_id=VALUES(quiz_id);

-- $quizId = $pdo->lastInsertId();

-- Link quiz → categories
-- INSERT INTO quiz_categories (quiz_id, category_id)
-- VALUES (?, ?);

