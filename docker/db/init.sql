-- docker/db/init.sql
SET GLOBAL time_zone = 'Europe/Paris';

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

CREATE TABLE IF NOT EXISTS score_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    user_id INT NOT NULL,
    user_time INT NOT NULL,
    user_score INT NOT NULL,
    FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id)
        ON DELETE CASCADE,
    FOREIGN KEY (user_id)
        REFERENCES users(id)
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
    duration INT NOT NULL DEFAULT 0,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- SEEDING DATA
-- =========================================================================

-- 1. Seed exactly 10 distinct categories
INSERT INTO categories (id, label) VALUES 
(1, 'Computer Science & Web'),
(2, 'General Knowledge'),
(3, 'Sciences & Astronomy'),
(4, 'History & Geography'),
(5, 'Pop Culture & Gaming'),
(6, 'Movies & TV Shows'),
(7, 'Sports & Fitness'),
(8, 'Music & Arts'),
(9, 'Literature & Myths'),
(10, 'Nature & Animals')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 2. Test Accounts (password: admin123 hashed using PASSWORD_BCRYPT)
INSERT INTO users (id, username, email, password, role, avatar) VALUES 
(1, 'Admin', 'admin@quiz.fr', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'admin', 'racoon.png'),
(2, 'student', 'student@school.com', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'user', 'bee.png'),
(3, 'player_one', 'player1@gmail.com', '$2y$10$K9R7MrgYvPcdQdAsLdP1fuAFMVhTvVui5JHa8hg/BfB4fyjtmFX5m', 'user', 'monkey.png')
ON DUPLICATE KEY UPDATE username=VALUES(username);

-- 3. Seed extensive list of questions across all 10 categories (35 questions)
INSERT INTO questions (id, category_id, question_text, difficulty) VALUES 
-- Cat 1: Web
(1, 1, 'What does the acronym CSS stand for?', 'easy'),
(2, 1, 'Which protocol features encrypted data transmission for web browsing?', 'easy'),
(3, 1, 'Which programming language is mainly executed client-side in browsers?', 'medium'),
(4, 1, 'What does HTTP stand for?', 'easy'),
(5, 1, 'Which HTML5 tag is used to natively play video files?', 'easy'),
-- Cat 2: General Knowledge
(6, 2, 'Which corporate giant manufactures the iPhone?', 'easy'),
(7, 2, 'What is the standard currency used across the European Union?', 'easy'),
(8, 2, 'Which country is famous for the architectural landmark Taj Mahal?', 'easy'),
(9, 2, 'How many days are there in a standard leap year?', 'easy'),
(10, 2, 'Which language has the most native speakers in the world?', 'medium'),
-- Cat 3: Sciences & Astronomy
(11, 3, 'What is the approximate speed of light?', 'hard'),
(12, 3, 'Which planet in our solar system is known for its prominent ring system?', 'easy'),
(13, 3, 'What is the primary gas found in the air we breathe?', 'medium'),
(14, 3, 'What is the chemical symbol for water?', 'easy'),
(15, 3, 'Which organ is responsible for pumping blood throughout the human body?', 'easy'),
-- Cat 4: History & Geo
(16, 4, 'In which year did the French Revolution begin?', 'medium'),
(17, 4, 'What is the capital city of Australia?', 'medium'),
(18, 4, 'Which river is widely considered the longest on planet Earth?', 'medium'),
(19, 4, 'Which country gifted the Statue of Liberty to the United States?', 'easy'),
(20, 4, 'What is the smallest independent country in the world?', 'easy'),
-- Cat 5: Pop Culture & Gaming
(21, 5, 'Which Japanese company created the iconic franchise Super Mario?', 'easy'),
(22, 5, 'What is the name of the pixelated sandbox game that became the best-selling video game of all time?', 'easy'),
(23, 5, 'In Pac-Man, how many ghosts chase the player around the maze?', 'easy'),
(24, 5, 'What is the name of the continent where World of Warcraft takes place?', 'medium'),
(25, 5, 'Which video game console released in 2000 is the best-selling console of all time?', 'medium'),
-- Cat 6: Movies & TV Shows
(26, 6, 'Who directed the 2010 sci-fi blockbuster movie Inception?', 'medium'),
(27, 6, 'What is the highest-grossing film of all time worldwide?', 'hard'),
(28, 6, 'How many Academy Awards (Oscars) did the movie Titanic win?', 'hard'),
(29, 6, 'What is the name of the fictional kingdom where Game of Thrones mainly takes place?', 'easy'),
(30, 6, 'Which actor played the iconic character of Wolverine in the X-Men films?', 'easy'),
-- Cat 7: Sports
(31, 7, 'How many players are on the field for a single team in a soccer match?', 'easy'),
(32, 7, 'Every how many years are the Olympic Games hosted?', 'easy'),
(33, 7, 'In tennis, what word is used to describe a score of zero?', 'medium'),
(34, 7, 'Which country has won the most FIFA World Cup trophies?', 'medium'),
(35, 7, 'How many points is a standard slam dunk worth in basketball?', 'easy'),
-- Cat 8: Music & Arts
(36, 8, 'Which famous Renaissance artist painted the Mona Lisa?', 'easy'),
(37, 8, 'How many strings does a standard classical violin have?', 'medium'),
(38, 8, 'Which musical icon is widely referred to as the King of Pop?', 'easy'),
(39, 8, 'Which instrument has 88 keys and belongs to the percussion/string family?', 'easy'),
(40, 8, 'Who composed the famous Symphony No. 9 which includes the Ode to Joy?', 'medium'),
-- Cat 9: Literature & Myths
(41, 9, 'Who wrote the timeless tragedy Romeo and Juliet?', 'easy'),
(42, 9, 'In Greek mythology, who is the absolute ruler of the Gods on Mount Olympus?', 'easy'),
(43, 9, 'What is the name of the dynamic detective created by Arthur Conan Doyle?', 'easy'),
(44, 9, 'Who is the ultimate wizard headmaster of Hogwarts in the Harry Potter series?', 'easy'),
(45, 9, 'According to Norse legend, what is the name of Thors magical war hammer?', 'medium'),
-- Cat 10: Nature & Animals
(46, 10, 'What is the largest living mammal currently on earth?', 'easy'),
(47, 10, 'Which animal is famously known as the King of the Jungle?', 'easy'),
(48, 10, 'How many hearts does an octopus have?', 'medium'),
(49, 10, 'What type of bird is physically incapable of backward flight?', 'easy'),
(50, 10, 'What is the only mammal capable of true, sustained flight?', 'easy'),
-- Bulk general knowledge expansion for the 20-question quiz
(51, 2, 'What is the capital of Japan?', 'easy'),
(52, 2, 'Which planet is closest to the Sun?', 'easy'),
(53, 2, 'What do bees collect to make honey?', 'easy'),
(54, 2, 'How many primary colors are there?', 'easy'),
(55, 2, 'Which famous ship sank on its maiden voyage in 1912?', 'easy')
ON DUPLICATE KEY UPDATE question_text=VALUES(question_text);

-- 4. Seed answers / choices for every single question
INSERT INTO answers (id, question_id, answer_text, is_correct) VALUES 
-- Cat 1: Web
(1, 1, 'Cascading Style Sheets', 1), (2, 1, 'Creative Style System', 0),
(3, 2, 'HTTP', 0), (4, 2, 'HTTPS', 1),
(5, 3, 'PHP', 0), (6, 3, 'JavaScript', 1),
(7, 4, 'Hypertext Transfer Protocol', 1), (8, 4, 'High Tech Text Protocol', 0),
(9, 5, '<media>', 0), (10, 5, '<video>', 1),

-- Cat 2: General Knowledge
(11, 6, 'Microsoft', 0), (12, 6, 'Apple', 1),
(13, 7, 'Euro', 1), (14, 7, 'Dollar', 0),
(15, 8, 'Egypt', 0), (16, 8, 'India', 1),
(17, 9, '366', 1), (18, 9, '365', 0),
(19, 10, 'English', 0), (20, 10, 'Mandarin Chinese', 1),

-- Cat 3: Sciences & Astronomy
(21, 11, '300,000 km/s', 1), (22, 11, '150,000 km/s', 0),
(23, 12, 'Mars', 0), (24, 12, 'Saturn', 1),
(25, 13, 'Nitrogen', 1), (26, 13, 'Oxygen', 0),
(27, 14, 'CO2', 0), (28, 14, 'H2O', 1),
(29, 15, 'Heart', 1), (30, 15, 'Lungs', 0),

-- Cat 4: History & Geo
(31, 16, '1799', 0), (32, 16, '1789', 1),
(33, 17, 'Sydney', 0), (34, 17, 'Canberra', 1),
(35, 18, 'Nile River', 1), (36, 18, 'Amazon River', 0),
(37, 19, 'United Kingdom', 0), (38, 19, 'France', 1),
(39, 20, 'Vatican City', 1), (40, 20, 'Monaco', 0),

-- Cat 5: Pop Culture & Gaming
(41, 21, 'Sony', 0), (42, 21, 'Nintendo', 1),
(43, 22, 'Roblox', 0), (44, 22, 'Minecraft', 1),
(45, 23, '4', 1), (46, 23, '3', 0),
(47, 24, 'Pandaria', 0), (48, 24, 'Azeroth', 1),
(49, 25, 'PlayStation 2', 1), (50, 25, 'Nintendo Wii', 0),

-- Cat 6: Movies & TV Shows
(51, 26, 'Steven Spielberg', 0), (52, 26, 'Christopher Nolan', 1),
(53, 27, 'Avengers: Endgame', 0), (54, 27, 'Avatar', 1),
(55, 28, '11', 1), (56, 28, '8', 0),
(57, 29, 'Essos', 0), (58, 29, 'Westeros', 1),
(59, 30, 'Hugh Jackman', 1), (60, 30, 'Robert Downey Jr.', 0),

-- Cat 7: Sports
(61, 31, '9', 0), (62, 31, '11', 1),
(63, 32, '4 years', 1), (64, 32, '2 years', 0),
(65, 33, 'Zero', 0), (66, 33, 'Love', 1),
(67, 34, 'Brazil', 1), (68, 34, 'Germany', 0),
(69, 35, '3 points', 0), (70, 35, '2 points', 1),

-- Cat 8: Music & Arts
(71, 36, 'Vincent van Gogh', 0), (72, 36, 'Leonardo da Vinci', 1),
(73, 37, '4 strings', 1), (74, 37, '6 strings', 0),
(75, 38, 'Elvis Presley', 0), (76, 38, 'Michael Jackson', 1),
(77, 39, 'Piano', 1), (78, 39, 'Harp', 0),
(79, 40, 'Wolfgang Amadeus Mozart', 0), (80, 40, 'Ludwig van Beethoven', 1),

-- Cat 9: Literature & Myths
(81, 41, 'William Shakespeare', 1), (82, 41, 'Charles Dickens', 0),
(83, 42, 'Hades', 0), (84, 42, 'Zeus', 1),
(85, 43, 'Hercule Poirot', 0), (86, 43, 'Sherlock Holmes', 1),
(87, 44, 'Albus Dumbledore', 1), (88, 44, 'Severus Snape', 0),
(89, 45, 'Gungnir', 0), (90, 45, 'Mjolnir', 1),

-- Cat 10: Nature & Animals
(91, 46, 'African Elephant', 0), (92, 46, 'Blue Whale', 1),
(93, 47, 'Lion', 1), (94, 47, 'Tiger', 0),
(95, 48, '1', 0), (96, 48, '3', 1),
(97, 49, 'Hummingbird', 1), (98, 49, 'Eagle', 0),
(99, 50, 'Flying Squirrel', 0), (100, 50, 'Bat', 1),

-- Bulk Expansion
(101, 51, 'Kyoto', 0), (102, 51, 'Tokyo', 1),
(103, 52, 'Mercury', 1), (104, 52, 'Venus', 0),
(105, 53, 'Pollen', 0), (106, 53, 'Nectar', 1),
(107, 54, '5', 0), (108, 54, '3', 1),
(109, 55, 'Titanic', 1), (110, 55, 'Lusitania', 0)
ON DUPLICATE KEY UPDATE answer_text=VALUES(answer_text);

-- 5. Seed EXACTLY 10 Quiz configurations with custom sizes (3, 5, 10, 20 questions)
INSERT INTO quizzes (id, name, description, difficulty, question_count, is_active) VALUES 
(1, 'Quick Web Check', 'A quick test on modern web definitions.', 'easy', 3, 1),
(2, 'Classic Myths & Lore', 'Greek, Roman and British literature milestones.', 'medium', 3, 1),
(3, 'Hollywood Essentials', 'Sizing up top blockbuster directors and Academy records.', 'hard', 3, 1),
(4, 'The Sports Arena', 'Rules, records and field tracking parameters.', 'easy', 5, 1),
(5, 'Deep Dive Arts & Tunes', 'Piano setups, violin arrays and worldwide legendary music compositors.', 'medium', 5, 1),
(6, 'Wildlife Exploration', 'Mammal sizing, flight rules and structural anatomy of animals.', 'easy', 5, 1),
(7, 'Cosmos & Basic Chemistry', 'Deep physics speed tracking, planet layout and core structures.', 'hard', 5, 1),
(8, 'Echoes of History', 'Sizing up global continuous rivers, country borders and dynamic dates.', 'medium', 5, 1),
(9, 'The Gamers Database', 'Consoles history, best-selling blocks and retro lore tracking.', 'medium', 10, 1),
(10, 'The Absolute Brain-Crusher Marathon', 'A massive transversal quiz pulling data from all historical, technical, and general sources.', 'hard', 20, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

-- 6. Many-to-Many Connections: Quiz <-> Categories
INSERT INTO quiz_categories (quiz_id, category_id) VALUES 
(1, 1), (2, 9), (3, 6), (4, 7), (5, 8), (6, 10), (7, 3), (8, 4), (9, 5), (10, 2)
ON DUPLICATE KEY UPDATE quiz_id=VALUES(quiz_id);

-- 7. Many-to-Many Connections: Quiz <-> Questions (3 to 4 questions per quiz)
INSERT INTO quiz_questions (quiz_id, question_id) VALUES 
-- Quiz 1 : 3 Questions (Web)
(1, 1), (1, 2), (1, 3),
-- Quiz 2 : 3 Questions (Literature)
(2, 41), (2, 42), (2, 43),
-- Quiz 3 : 3 Questions (Movies)
(3, 26), (3, 27), (3, 28),

-- Quiz 4 : 5 Questions (Sports)
(4, 31), (4, 32), (4, 33), (4, 34), (4, 35),
-- Quiz 5 : 5 Questions (Music)
(5, 36), (5, 37), (5, 38), (5, 39), (5, 40),
-- Quiz 6 : 5 Questions (Nature)
(6, 46), (6, 47), (6, 48), (6, 49), (6, 50),
-- Quiz 7 : 5 Questions (Sciences)
(7, 11), (7, 12), (7, 13), (7, 14), (7, 15),
-- Quiz 8 : 5 Questions (History/Geo)
(8, 16), (8, 17), (8, 18), (8, 19), (8, 20),

-- $quizId = $pdo->lastInsertId();

-- Link quiz → categories
-- INSERT INTO quiz_categories (quiz_id, category_id)
-- VALUES (?, ?);
-- Quiz 9 : 10 Questions (Gaming + Extra Web)
(9, 21), (9, 22), (9, 23), (9, 24), (9, 25), (9, 1), (9, 2), (9, 3), (9, 4), (9, 5),

-- Quiz 10 : 20 Questions Marathon (Cross-categories bulk)
(10, 6), (10, 7), (10, 8), (10, 9), (10, 10), 
(10, 51), (10, 52), (10, 53), (10, 54), (10, 55),
(10, 11), (10, 16), (10, 21), (10, 26), (10, 31),
(10, 36), (10, 41), (10, 46), (10, 12), (10, 17)
ON DUPLICATE KEY UPDATE quiz_id=VALUES(quiz_id);

INSERT INTO score_users (quiz_id, user_id, user_time, user_score) VALUES
(2, 2, 9, 3)