-- Question Data Seeder - Raw SQL INSERT Statements
-- Make sure to run migrations first: php artisan migrate

-- 1. QUESTIONS TABLE DATA
INSERT INTO `questions` (`id`, `exam_section_id`, `question_text`, `question_type`, `instructions`, `is_active`, `created_at`, `updated_at`) VALUES (NULL, '1', 'What is the capital of France?', 'mcq', 'Choose the correct answer.', '1', '2025-10-20 10:00:00', '2025-10-20 10:00:00');

INSERT INTO `questions` (`id`, `exam_section_id`, `question_text`, `question_type`, `instructions`, `is_active`, `created_at`, `updated_at`) VALUES (NULL, '1', 'Explain the concept of photosynthesis in detail.', 'essay', 'Write a comprehensive explanation with examples.', '1', '2025-10-20 10:01:00', '2025-10-20 10:01:00');

INSERT INTO `questions` (`id`, `exam_section_id`, `question_text`, `question_type`, `instructions`, `is_active`, `created_at`, `updated_at`) VALUES (NULL, '1', 'Based on the following passage, answer the questions below.', 'paragraph_mcq', 'Read the passage carefully and select the best answer.', '1', '2025-10-20 10:02:00', '2025-10-20 10:02:00');

INSERT INTO `questions` (`id`, `exam_section_id`, `question_text`, `question_type`, `instructions`, `is_active`, `created_at`, `updated_at`) VALUES (NULL, '1', 'Which programming language is known for web development?', 'mcq', 'Select the most appropriate answer.', '1', '2025-10-20 10:03:00', '2025-10-20 10:03:00');

INSERT INTO `questions` (`id`, `exam_section_id`, `question_text`, `question_type`, `instructions`, `is_active`, `created_at`, `updated_at`) VALUES (NULL, '1', 'Describe the water cycle process.', 'essay', 'Provide a detailed explanation with diagrams if possible.', '1', '2025-10-20 10:04:00', '2025-10-20 10:04:00');

-- 2. QUESTION_OPTIONS TABLE DATA
-- Options for Question 1 (Capital of France)
INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '1', 'Paris', '1', '2025-10-20 10:05:00', '2025-10-20 10:05:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '1', 'London', '0', '2025-10-20 10:05:00', '2025-10-20 10:05:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '1', 'Berlin', '0', '2025-10-20 10:05:00', '2025-10-20 10:05:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '1', 'Madrid', '0', '2025-10-20 10:05:00', '2025-10-20 10:05:00');

-- Options for Question 4 (Programming Language)
INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '4', 'JavaScript', '1', '2025-10-20 10:06:00', '2025-10-20 10:06:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '4', 'Assembly', '0', '2025-10-20 10:06:00', '2025-10-20 10:06:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '4', 'COBOL', '0', '2025-10-20 10:06:00', '2025-10-20 10:06:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '4', 'Fortran', '0', '2025-10-20 10:06:00', '2025-10-20 10:06:00');

-- Options for Question 3 (Paragraph MCQ - Climate Change)
INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '3', 'Since the mid-20th century', '1', '2025-10-20 10:07:00', '2025-10-20 10:07:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '3', 'Since the 19th century', '0', '2025-10-20 10:07:00', '2025-10-20 10:07:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '3', 'Since the 18th century', '0', '2025-10-20 10:07:00', '2025-10-20 10:07:00');

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`) VALUES (NULL, '3', 'Since the 21st century', '0', '2025-10-20 10:07:00', '2025-10-20 10:07:00');

-- 3. QUESTION_PARAGRAPHS TABLE DATA
-- Paragraph for Question 3 (Climate Change)
INSERT INTO `question_paragraphs` (`id`, `question_id`, `paragraph_content`, `created_at`, `updated_at`) VALUES (NULL, '3', 'Climate change is one of the most pressing issues of our time. It refers to long-term shifts in global temperatures and weather patterns. While climate variations are natural, scientific evidence shows that human activities have been the main driver of climate change since the mid-20th century. The primary cause is the greenhouse effect, where certain gases in the atmosphere trap heat from the sun. The main greenhouse gases include carbon dioxide, methane, and nitrous oxide. These gases are released through activities such as burning fossil fuels, deforestation, and industrial processes. The consequences of climate change are far-reaching and include rising sea levels, more frequent extreme weather events, loss of biodiversity, and threats to food security. Addressing climate change requires immediate and sustained action from governments, businesses, and individuals worldwide.', '2025-10-20 10:08:00', '2025-10-20 10:08:00');

-- 4. QUESTION_ANSWERS TABLE DATA
-- Answer for Question 1 (MCQ - Capital of France) - Option ID 1 is correct
INSERT INTO `question_answers` (`id`, `question_id`, `answer_text`, `correct_option_id`, `created_at`, `updated_at`) VALUES (NULL, '1', NULL, '1', '2025-10-20 10:09:00', '2025-10-20 10:09:00');

-- Answer for Question 2 (Essay - Photosynthesis)
INSERT INTO `question_answers` (`id`, `question_id`, `answer_text`, `correct_option_id`, `created_at`, `updated_at`) VALUES (NULL, '2', 'Sample answer: Photosynthesis is the process by which plants convert light energy into chemical energy, using carbon dioxide and water to produce glucose and oxygen. This process occurs in the chloroplasts of plant cells and is essential for life on Earth.', NULL, '2025-10-20 10:09:00', '2025-10-20 10:09:00');

-- Answer for Question 3 (Paragraph MCQ - Climate Change) - Option ID 9 is correct
INSERT INTO `question_answers` (`id`, `question_id`, `answer_text`, `correct_option_id`, `created_at`, `updated_at`) VALUES (NULL, '3', NULL, '9', '2025-10-20 10:09:00', '2025-10-20 10:09:00');

-- Answer for Question 4 (MCQ - Programming Language) - Option ID 5 is correct
INSERT INTO `question_answers` (`id`, `question_id`, `answer_text`, `correct_option_id`, `created_at`, `updated_at`) VALUES (NULL, '4', NULL, '5', '2025-10-20 10:09:00', '2025-10-20 10:09:00');

-- Answer for Question 5 (Essay - Water Cycle)
INSERT INTO `question_answers` (`id`, `question_id`, `answer_text`, `correct_option_id`, `created_at`, `updated_at`) VALUES (NULL, '5', 'Sample answer: The water cycle is the continuous movement of water through evaporation, condensation, and precipitation. Water evaporates from oceans, lakes, and rivers, forms clouds, and falls back as rain or snow.', NULL, '2025-10-20 10:09:00', '2025-10-20 10:09:00');
