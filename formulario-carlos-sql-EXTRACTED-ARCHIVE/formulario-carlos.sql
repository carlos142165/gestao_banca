-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 31/10/2025 às 01:04
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `formulario-carlos`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` varchar(255) NOT NULL COMMENT 'Descrição da ação realizada',
  `dados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dados adicionais em formato JSON' CHECK (json_valid(`dados`)),
  `ip_origem` varchar(45) DEFAULT NULL COMMENT 'IP do servidor/cliente',
  `data_criacao` datetime DEFAULT current_timestamp() COMMENT 'Data e hora da ação'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de todas as atividades do painel administrativo';

-- --------------------------------------------------------

--
-- Estrutura para tabela `assinaturas`
--

CREATE TABLE `assinaturas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_plano` int(11) NOT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime DEFAULT NULL,
  `status` enum('ativa','cancelada','expirada','pendente') DEFAULT 'ativa',
  `tipo_ciclo` enum('mensal','anual') NOT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `id_mercadopago` varchar(255) DEFAULT NULL,
  `id_preferencia_mercadopago` varchar(255) DEFAULT NULL,
  `modo_pagamento` varchar(50) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartoes_salvos`
--

CREATE TABLE `cartoes_salvos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token_mercadopago` varchar(255) DEFAULT NULL,
  `ultimos_digitos` varchar(4) DEFAULT NULL,
  `bandeira` varchar(50) DEFAULT NULL,
  `titular_cartao` varchar(100) DEFAULT NULL,
  `mes_expiracao` int(11) DEFAULT NULL,
  `ano_expiracao` int(11) DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `controle`
--

CREATE TABLE `controle` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `deposito` decimal(10,2) DEFAULT NULL,
  `diaria` decimal(5,2) DEFAULT 0.00,
  `saque` int(11) DEFAULT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  `origem` enum('banca','mentor') DEFAULT 'banca',
  `unidade` decimal(10,2) DEFAULT 0.00,
  `odds` decimal(5,2) DEFAULT NULL,
  `meta` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `controle`
--

INSERT INTO `controle` (`id`, `id_usuario`, `deposito`, `diaria`, `saque`, `data_registro`, `origem`, `unidade`, `odds`, `meta`) VALUES
(17, 28, 0.00, 0.00, 0, '2025-06-28 01:37:26', 'banca', 0.00, NULL, NULL),
(18, 28, 0.00, 0.00, 0, '2025-06-28 01:40:41', 'banca', 0.00, NULL, NULL),
(19, 28, 544.00, 4.00, 52, '2025-06-28 01:47:45', 'banca', 0.00, NULL, NULL),
(20, 28, 80000.00, 1.00, 75, '2025-06-28 02:09:13', 'banca', 0.00, NULL, NULL),
(67, 33, 12121.00, NULL, NULL, '2025-07-01 00:32:21', 'banca', 0.00, NULL, NULL),
(68, 33, NULL, 5.00, NULL, '2025-07-01 00:32:38', 'banca', 0.00, NULL, NULL),
(121, 30, 100.00, NULL, NULL, '2025-07-04 12:43:49', 'banca', 0.00, NULL, NULL),
(122, 30, NULL, 10.00, NULL, '2025-07-04 12:44:00', 'banca', 0.00, NULL, NULL),
(327, 36, 100000.00, 0.00, NULL, '2025-07-05 21:15:42', 'banca', 0.00, NULL, NULL),
(328, 36, NULL, 1.00, NULL, '2025-07-05 21:16:20', 'banca', 0.00, NULL, NULL),
(346, 35, 1000.00, 0.00, NULL, '2025-07-18 19:25:43', 'banca', 0.00, NULL, NULL),
(347, 35, NULL, 0.00, 100, '2025-07-18 19:26:20', 'banca', 0.00, NULL, NULL),
(348, 35, NULL, 5.00, NULL, '2025-07-19 11:20:36', 'banca', 0.00, NULL, NULL),
(349, 35, NULL, 10.00, NULL, '2025-07-19 14:06:04', 'banca', 0.00, NULL, NULL),
(350, 31, 1000.00, 0.00, NULL, '2025-07-19 18:12:15', 'banca', 0.00, NULL, NULL),
(351, 31, NULL, 5.00, NULL, '2025-07-19 18:12:32', 'banca', 0.00, NULL, NULL),
(364, 37, 1000.00, 0.00, NULL, '2025-07-19 23:50:55', 'banca', 0.00, NULL, NULL),
(365, 37, NULL, 10.00, NULL, '2025-07-19 23:51:21', 'banca', 0.00, NULL, NULL),
(366, 38, 1000.00, 0.00, NULL, '2025-07-20 00:00:44', 'banca', 0.00, NULL, NULL),
(367, 38, NULL, 5.00, NULL, '2025-07-20 00:01:00', 'banca', 0.00, NULL, NULL),
(370, 35, NULL, 1.00, NULL, '2025-07-20 19:39:32', 'banca', 0.00, NULL, NULL),
(371, 35, NULL, 5.00, NULL, '2025-07-20 20:14:03', 'banca', 0.00, NULL, NULL),
(383, 29, 1000.00, 0.00, NULL, '2025-07-22 18:53:06', 'banca', 0.00, NULL, NULL),
(384, 29, NULL, 5.00, NULL, '2025-07-22 18:53:45', 'banca', 0.00, NULL, NULL),
(385, 29, NULL, 0.00, 100, '2025-07-22 18:59:27', 'banca', 0.00, NULL, NULL),
(386, 29, NULL, 0.00, 25, '2025-07-22 19:11:47', 'banca', 0.00, NULL, NULL),
(387, 29, NULL, 2.00, NULL, '2025-07-22 19:45:17', 'banca', 0.00, NULL, NULL),
(430, 40, 200.00, 0.00, NULL, '2025-07-25 17:29:50', 'banca', 0.00, NULL, NULL),
(431, 40, NULL, 10.00, NULL, '2025-07-25 17:30:22', 'banca', 0.00, NULL, NULL),
(432, 40, 200.00, 0.00, NULL, '2025-07-25 17:30:40', 'banca', 0.00, NULL, NULL),
(433, 40, 100.00, 0.00, NULL, '2025-07-25 17:42:23', 'banca', 0.00, NULL, NULL),
(434, 40, 600.00, 0.00, NULL, '2025-07-25 17:44:58', 'banca', 0.00, NULL, NULL),
(435, 40, NULL, 2.00, NULL, '2025-07-25 17:45:08', 'banca', 1.00, 1.50, NULL),
(839, 39, 1000.00, 1.00, NULL, '2025-08-16 21:21:26', 'banca', 2.00, 1.50, NULL),
(863, 40, NULL, 2.00, 100, '2025-08-18 17:12:44', 'banca', 1.00, 1.50, NULL),
(939, 42, 1000.00, 1.00, NULL, '2025-08-24 11:43:05', 'banca', 2.00, 1.50, 'Meta Fixa'),
(965, 42, NULL, 1.00, 250, '2025-09-03 16:36:39', 'banca', 1.00, 1.50, 'Meta Fixa'),
(993, 43, 1000.00, 1.00, NULL, '2025-10-11 14:26:24', 'banca', 1.00, 1.50, 'Meta Fixa'),
(995, 41, 1000.00, 1.00, NULL, '2025-10-16 20:11:25', 'banca', 1.00, 1.50, 'Meta Turbo'),
(997, 23, 1000.00, 1.00, NULL, '2025-10-16 15:46:00', 'banca', 1.00, 1.50, 'Meta Turbo'),
(998, 23, NULL, 1.00, NULL, '2025-10-16 15:46:31', 'banca', 1.00, 1.50, 'Meta Turbo'),
(999, 23, NULL, 1.00, NULL, '2025-10-16 15:50:23', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1000, 23, NULL, 1.00, NULL, '2025-10-16 16:04:42', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1001, 23, NULL, 1.00, NULL, '2025-10-16 16:20:50', 'banca', 1.00, 1.50, 'Meta Turbo'),
(1002, 23, NULL, 1.00, NULL, '2025-10-16 16:23:22', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1003, 23, NULL, 1.00, NULL, '2025-10-16 16:24:51', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1004, 23, NULL, 1.00, NULL, '2025-10-16 16:32:39', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1005, 23, NULL, 1.00, NULL, '2025-10-16 16:36:01', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1006, 23, NULL, 1.00, NULL, '2025-10-16 16:51:39', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1007, 23, NULL, 1.00, NULL, '2025-10-16 17:12:04', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1008, 23, NULL, 1.00, NULL, '2025-10-16 23:01:38', 'banca', 1.00, 1.00, 'Meta Turbo'),
(1011, 41, NULL, 1.00, NULL, '2025-10-16 20:11:40', 'banca', 1.00, 1.00, 'Meta Fixa'),
(1018, 23, NULL, 1.00, NULL, '2025-10-18 11:12:11', 'banca', 1.00, 1.50, 'Meta Turbo'),
(1019, 44, NULL, 2.00, NULL, '2025-10-17 00:06:38', 'banca', 1.00, 1.00, 'Meta Fixa'),
(1020, 44, 1000.00, 1.00, NULL, '2025-10-18 21:57:49', 'banca', 1.00, 1.50, 'Meta Turbo'),
(1022, 44, NULL, 1.00, NULL, '2025-10-26 10:26:44', 'banca', 1.00, 1.50, 'Meta Fixa'),
(1029, 23, NULL, 1.00, NULL, '2025-10-19 14:07:15', 'banca', 1.00, 1.00, 'Meta Fixa'),
(1030, 23, NULL, 1.00, 15, '2025-10-19 14:07:58', 'banca', 1.00, 1.00, 'Meta Fixa'),
(1031, 23, 15.00, 1.00, NULL, '2025-10-19 16:22:09', 'banca', 1.00, 1.50, 'Meta Turbo'),
(1032, 23, NULL, 1.00, NULL, '2025-10-27 22:27:27', 'banca', 1.00, 1.50, 'Meta Fixa'),
(1033, 45, NULL, 2.00, NULL, '2025-10-20 20:35:32', 'banca', 1.00, 1.00, 'Meta Fixa'),
(1034, 45, 1000.00, 1.00, NULL, '2025-10-20 20:35:50', 'banca', 1.00, 1.50, 'Meta Fixa');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_banca`
--

CREATE TABLE `historico_banca` (
  `id_usuario` int(11) DEFAULT NULL,
  `saldo_anterior` decimal(10,2) DEFAULT NULL,
  `data_atualizacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `historico_banca`
--

INSERT INTO `historico_banca` (`id_usuario`, `saldo_anterior`, `data_atualizacao`) VALUES
(40, 0.00, '2025-07-24 20:42:41'),
(40, 0.00, '2025-07-24 20:56:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mentores`
--

CREATE TABLE `mentores` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `mentores`
--

INSERT INTO `mentores` (`id`, `id_usuario`, `foto`, `nome`, `data_criacao`) VALUES
(6, 33, '686c87cec1a82.jpg', 'carlos tips', '2025-07-08 02:51:58'),
(35, 35, '6876ea32e1954.jpg', 'Duplinha Do Mika', '2025-07-15 23:54:26'),
(36, 35, '6876ea77aa9fe.jpg', 'Desdobramento', '2025-07-15 23:55:35'),
(37, 35, '6877087938f2c.jpg', 'Tulio', '2025-07-15 23:57:02'),
(38, 35, '6876eaf925f81.jpg', 'Carlos Tipss', '2025-07-15 23:57:45'),
(47, 36, '6878244698458.jpg', 'Lucas.bet', '2025-07-16 22:14:30'),
(79, 33, '687ad1988ee0b.jpg', 'Duplinha Do Mika', '2025-07-18 22:58:32'),
(80, 30, '687ad235ab6cb.jpg', 'Haker Bet', '2025-07-18 23:01:09'),
(81, 30, '687ad26cecd16.jpg', 'Flor Bet', '2025-07-18 23:02:04'),
(82, 30, '687ad282a6adc.jpg', 'Tulio Bet', '2025-07-18 23:02:26'),
(83, 35, '687bf752ac80d.jpg', 'Flor.bet', '2025-07-19 19:51:46'),
(87, 38, '687c5c4ecd781.jpg', 'Kely', '2025-07-20 03:02:38'),
(95, 23, '68fd898bda0f5.jpg', '+1 Gol As...', '2025-07-21 17:04:27'),
(96, 23, '68fd8b55ce440.jpg', 'Cry.bet', '2025-07-21 17:04:45'),
(97, 23, '68fd8bf3b3ab9.png', 'Aviator', '2025-07-21 17:05:19'),
(101, 29, '688009ff1dc86.jpg', 'Tulio Bet', '2025-07-22 22:00:31'),
(108, 39, '6882a1463384b.jpg', 'Mika', '2025-07-24 20:53:24'),
(115, 40, '6882b0a41368e.jpg', 'Teste 4', '2025-07-24 22:16:04'),
(123, 23, '68fd8caf30a95.jpg', '+1 Cantos As.', '2025-07-26 23:08:09'),
(173, 39, '68a120585ce18.jpg', 'K12', '2025-08-17 00:20:40'),
(201, 42, '68b8329a94880.png', 'Dan Aviator', '2025-08-24 14:43:57'),
(244, 41, '68b1cadbd93d7.jpg', 'Ana Bet', '2025-08-29 15:44:27'),
(266, 45, '68f6c784be8fb.jpg', 'Mika', '2025-09-07 16:24:42'),
(271, 41, '68be496084304.jpg', 'Florbet', '2025-09-08 03:11:28'),
(272, 41, '68be497a67a88.jpg', 'Tulio Bet', '2025-09-08 03:11:54'),
(274, 41, '68be49aacc554.jpg', 'Haccker Bet', '2025-09-08 03:12:42'),
(280, 43, '68d70c89b8f96.jpg', 'Hacker Bet', '2025-09-26 21:58:33'),
(281, 43, '68d71e8528519.jpg', 'Desdobramento', '2025-09-26 23:15:17'),
(282, 43, '68d71ea2d8557.jpg', 'Tulio Bet', '2025-09-26 23:15:46'),
(283, 43, '68d71ec1a899b.jpg', 'Mika', '2025-09-26 23:16:17'),
(284, 43, '68d71eebc983d.jpg', 'Carlos Tips', '2025-09-26 23:16:59'),
(285, 41, '68eba0a1e10d5.jpg', 'K12 Bet', '2025-10-12 12:35:45'),
(286, 44, '68f16b4427fd1.jpg', 'Hacker Bet', '2025-10-16 22:01:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_mes` decimal(10,2) NOT NULL,
  `preco_ano` decimal(10,2) NOT NULL,
  `mentores_limite` int(11) DEFAULT 1,
  `entradas_diarias` int(11) DEFAULT 3,
  `ativo` tinyint(1) DEFAULT 1,
  `icone` varchar(50) DEFAULT NULL,
  `cor_tema` varchar(20) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `nome`, `descricao`, `preco_mes`, `preco_ano`, `mentores_limite`, `entradas_diarias`, `ativo`, `icone`, `cor_tema`, `data_criacao`) VALUES
(1, 'GRATUITO', 'Plano básico sem custo', 0.00, 0.00, 1, 3, 1, 'fas fa-gift', '#95a5a6', '2025-10-20 22:28:27'),
(2, 'PRATA', 'Plano intermediário com mais features', 15.99, 9.99, 5, 15, 1, 'fas fa-coins', '#c0392b', '2025-10-20 22:28:27'),
(3, 'OURO', 'Plano avançado com mais recursos', 29.99, 19.99, 10, 30, 1, 'fas fa-star', '#f39c12', '2025-10-20 22:28:27'),
(4, 'DIAMANTE', 'Plano premium com tudo ilimitado', 49.99, 39.99, 999, 999, 1, 'fas fa-gem', '#2980b9', '2025-10-20 22:28:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes_mercadopago`
--

CREATE TABLE `transacoes_mercadopago` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_assinatura` int(11) DEFAULT NULL,
  `id_pago_mercadopago` varchar(255) DEFAULT NULL,
  `status_pagamento` varchar(50) DEFAULT NULL,
  `tipo_pagamento` varchar(50) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `resposta_mercadopago` longtext DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(100) NOT NULL,
  `telefone` varchar(45) NOT NULL,
  `genero` varchar(45) NOT NULL,
  `token_recuperacao` varchar(255) NOT NULL,
  `token_expira` datetime NOT NULL,
  `data_cadastro` datetime DEFAULT NULL,
  `status_assinatura` enum('ativa','expirada','trial') DEFAULT 'trial',
  `data_inicio_assinatura` datetime DEFAULT NULL,
  `data_fim_assinatura` datetime DEFAULT NULL,
  `id_plano` int(11) DEFAULT 1,
  `tipo_ciclo` enum('mensal','anual') DEFAULT 'mensal',
  `cartao_salvo` tinyint(1) DEFAULT 0,
  `token_cartao` varchar(255) DEFAULT NULL,
  `ultimos_4_digitos` varchar(4) DEFAULT NULL,
  `bandeira_cartao` varchar(50) DEFAULT NULL,
  `mercadopago_customer_id` varchar(255) DEFAULT NULL,
  `data_renovacao_automatica` datetime DEFAULT NULL,
  `renovacao_ativa` tinyint(1) DEFAULT 1,
  `plano` varchar(50) DEFAULT 'Gratuito',
  `tipo_pagamento` enum('pago','bonus') DEFAULT 'pago'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `telefone`, `genero`, `token_recuperacao`, `token_expira`, `data_cadastro`, `status_assinatura`, `data_inicio_assinatura`, `data_fim_assinatura`, `id_plano`, `tipo_ciclo`, `cartao_salvo`, `token_cartao`, `ultimos_4_digitos`, `bandeira_cartao`, `mercadopago_customer_id`, `data_renovacao_automatica`, `renovacao_ativa`, `plano`, `tipo_pagamento`) VALUES
(23, 'carlos alberto silva dos santos', 'carlos142165@gmail.com', '$2y$10$BKBnJYa/QZ0/JJnhkMIRX.MZ74qQw3E0RBpZ3Brtnq6CVeSH1fPE2', '+55 (71) 98711-8929', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(28, 'lucas da silva', 'lukinha@gmail.com', '$2y$10$7Ctfc4J9vKymCq3hlZyxzO9SRn5X75w374ONB1wG.tUeo1aKpn0Wa', '+55 (71) 98711-8929', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'bonus'),
(29, 'tati da silva', 'tati@gmail.com', '$2y$10$cQcNf8gl7eMR8yLRbnMGpuX9R22pR4f1CKin2Hd9RKPIBRRLv86Z.', '+55 (71) 98711-8929', 'feminino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(30, 'adelia ferreira costa ', 'addlia@gmail.com', '$2y$10$TFWHIq7pocVNomOIts2cZ.olKdHPl2RoLJwbXX2WjR1jXBj05GIKS', '+55 (71) 98711-8929', 'feminino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(31, 'maicon da silva', 'maicon@gmail.com', '$2y$10$QHbn3WhvMjVXlM9LlLY0y.0GJBUlYJ05HnZsxsN.uPpwrs.5EoxGm', '+55 (71) 88711-8929', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'anual', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(33, 'alannes santos de carvalho', 'alannesca@gmail.com', '$2y$10$7Y0TIvIPgzh7hlNK5UAc0OapYJpuOZUTbmX3oPuQj.guNgKJEl.ta', '+55 (87) 11892-9999', 'feminino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'bonus'),
(34, 'lukinhas santos', 'luk@gmail.com', '$2y$10$ndOP9oabHF1zQqOEA135buBRW.OpB67RttxpVAzQUlOLXVG27IO.K', '+55 (71) 98711-8929', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(35, 'italo silva', 'italo@gmail.com', '$2y$10$PHQfVCPNjw22I9nXXA1GXOoCVVi4obtkjJhEoT40ctPGi6iU7hExO', '+55 (71) 98711-8929', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(36, 'paulo silva', 'pl@gmail.com', '$2y$10$PQrYPWAHbYOd/XbPLjR8O.A.4EsPe11jjC3RsYLWbm49zxaqCmQ9O', '+55 (71) 98711-5689', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(37, 'rafinha santos', 'rafinha@gmail.com', '$2y$10$eHrtBkYG5YijAX/cDQIUeenuSniGGMuCI.okjfiIyycdrRMc9Kc9W', '+55 (71) 98711-8929', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(38, 'kely santos ', 'kely@gmail.com', '$2y$10$tnpThp7ID.X1GVRr9K7OK.vu5kbR/7/DEHFa7YXd8wEh0rRBFpP.O', '+55 (87) 11895-6666', 'feminino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(39, 'zorba silva', 'zorba@gmail.com', '$2y$10$Ow0nlhqoa9/5DjkINi2GiO3snMD8w31sieDBPOWs7VGgOWDL8VO/2', '+55 (11) 11111-1111', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(40, 'aline alves', 'aline@gmail.com', '$2y$10$FXNeuRKlVwCsbjP4aqeNPeIEfABpNiRa2ZVr.Mb5rbYqy0EE.JYc2', '+55 (71) 98888-8888', 'feminino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'anual', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(41, 'testando bet', 'bet@gmail.com', '$2y$10$TzWsH/WpCZam6WQwCqP1G.I5TA3fZs0MPEjhwRAcLOFQIozi.m.tW', '+55 (87) 11111-1111', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(42, 'alannes santos Carvalho', 'alannes89536524@gmail.com', '$2y$10$Cs9lGgpQNBg5xXvrXhcz8OznFD6RhRJ7joGv3YfGLqqnfiqyZK5rq', '+55 (71) 99212-6212', 'feminino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago'),
(43, 'kaio sampaio', 'sampaio@gmail.com', '$2y$10$KOIeCxnOSgkk2T5eXiyQJOAbHZHYDkCLHMux6yM3sDlwbx.1JO5Qu', '+55 (99) 99999-9999', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, '2025-11-28 00:00:00', 2, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'bonus'),
(44, 'marlon da silva', 'mrlon@gmail.com', '$2y$10$yt0WYY4DtfiTOiE0/5jf6uwgZj85kIR0JEfIfMd2MOMdZdmeQpio6', '+55 (99) 99999-9999', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'bonus'),
(45, 'flavio silva dos santos', 'flavio@gmail.com', '$2y$10$12AoLFJSgnUDk3Yoo009dOZs3SE5g0qdpmgbD8aE8T.psdDtSgFE6', '+55 (71) 98711-8929', 'masculino', '', '0000-00-00 00:00:00', NULL, 'trial', NULL, NULL, 1, 'mensal', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Gratuito', 'pago');

-- --------------------------------------------------------

--
-- Estrutura para tabela `valor_mentores`
--

CREATE TABLE `valor_mentores` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_mentores` int(11) NOT NULL,
  `green` int(11) DEFAULT NULL,
  `red` int(11) DEFAULT NULL,
  `valor_green` decimal(10,2) DEFAULT NULL,
  `valor_red` decimal(10,2) DEFAULT NULL,
  `data_criacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `valor_mentores`
--

INSERT INTO `valor_mentores` (`id`, `id_usuario`, `id_mentores`, `green`, `red`, `valor_green`, `valor_red`, `data_criacao`) VALUES
(354, 33, 79, 1, 0, 10.00, NULL, '2025-07-19 00:58:49'),
(356, 30, 82, 1, 0, 20.00, NULL, '2025-07-19 01:03:03'),
(357, 30, 81, 0, 1, NULL, 10.00, '2025-07-19 01:03:20'),
(363, 35, 35, 0, 1, NULL, 3.00, '2025-07-19 02:51:02'),
(373, 35, 37, 1, 0, 10.00, NULL, '2025-07-19 04:00:32'),
(374, 35, 36, 1, 0, 10.00, NULL, '2025-07-19 04:23:34'),
(427, 35, 83, 0, 1, NULL, 17.00, '2025-07-19 22:36:33'),
(428, 35, 35, 1, 0, 5.00, NULL, '2025-07-19 22:38:19'),
(451, 35, 35, 0, 1, NULL, 5.00, '2025-07-20 04:29:21'),
(517, 39, 92, 1, 0, 35.00, NULL, '2025-07-20 19:34:14'),
(518, 39, 92, 1, 0, 15.00, NULL, '2025-07-20 19:34:39'),
(519, 39, 92, 1, 0, 1.00, NULL, '2025-07-20 19:34:53'),
(535, 35, 38, 1, 0, 10.00, NULL, '2025-07-21 01:15:23'),
(536, 35, 36, 1, 0, 12.50, NULL, '2025-07-21 01:50:55'),
(537, 35, 36, 1, 0, 5.00, NULL, '2025-07-21 01:51:27'),
(550, 39, 99, 1, 0, 10.00, NULL, '2025-07-22 23:49:56'),
(551, 29, 100, 1, 0, 100.00, NULL, '2025-07-22 23:57:59'),
(606, 40, 115, 0, 1, NULL, 100.00, '2025-07-25 04:06:48'),
(617, 40, 115, 1, 0, 100.00, NULL, '2025-07-25 22:45:28'),
(801, 687, 1, 1, 0, 10.00, NULL, '2025-07-27 10:22:00'),
(1749, 40, 115, 1, 0, 5.00, NULL, '2025-08-18 17:10:16'),
(1750, 40, 115, 1, 0, 7.00, NULL, '2025-08-18 17:10:16'),
(2413, 42, 201, 1, 0, 6.00, NULL, '2025-09-03 09:16:53'),
(2425, 42, 201, 1, 0, 4.00, NULL, '2025-09-03 23:21:21'),
(2436, 42, 201, 1, 0, 5.28, NULL, '2025-09-05 10:57:43'),
(2437, 42, 201, 1, 0, 2.35, NULL, '2025-09-05 11:14:38'),
(2493, 42, 201, 1, 0, 10.00, NULL, '2025-09-08 11:17:56'),
(2494, 42, 201, 1, 0, 10.00, NULL, '2025-09-08 11:54:56'),
(2521, 42, 201, 1, 0, 10.00, NULL, '2025-09-12 10:50:36'),
(2530, 42, 201, 1, 0, 10.00, NULL, '2025-09-15 20:08:32'),
(2905, 43, 280, 1, 0, 5.00, NULL, '2025-10-07 08:11:35'),
(2906, 43, 280, 1, 0, 5.00, NULL, '2025-10-07 08:13:56'),
(2909, 43, 280, 0, 1, NULL, 5.00, '2025-10-07 08:52:36'),
(2910, 43, 280, 1, 0, 5.00, NULL, '2025-10-07 09:05:35'),
(2911, 43, 280, 1, 0, 240.00, NULL, '2025-10-07 09:06:08'),
(2917, 41, 244, 1, 0, 10.00, NULL, '2025-10-07 12:21:04'),
(2918, 41, 244, 1, 0, 10.00, NULL, '2025-10-07 12:21:42'),
(2926, 41, 244, 1, 0, 20.00, NULL, '2025-10-08 08:18:20'),
(2932, 43, 280, 1, 0, 10.00, NULL, '2025-10-11 14:28:28'),
(2988, 41, 244, 0, 1, NULL, 50.00, '2025-10-13 22:36:31'),
(3001, 23, 95, 1, 0, 5.00, NULL, '2025-10-14 22:00:10'),
(3005, 41, 271, 1, 0, 30.10, NULL, '2025-10-15 06:45:09'),
(3050, 23, 95, 1, 0, 10.00, NULL, '2025-10-16 23:14:23'),
(3052, 44, 286, 1, 0, 10.00, NULL, '2025-10-17 00:09:31'),
(3064, 44, 286, 1, 0, 10.00, NULL, '2025-10-18 22:01:15'),
(3066, 44, 286, 1, 0, 0.11, NULL, '2025-10-18 22:03:13'),
(3096, 23, 95, 1, 0, 10.00, NULL, '2025-10-19 23:51:26'),
(3117, 23, 95, 1, 0, 10.00, NULL, '2025-10-20 01:06:28'),
(3126, 23, 95, 1, 0, 5.00, NULL, '2025-10-21 14:57:31'),
(3129, 23, 95, 1, 0, 1.10, NULL, '2025-10-21 14:58:00'),
(3130, 23, 96, 1, 0, 1.00, NULL, '2025-10-21 15:24:03'),
(3155, 45, 266, 1, 0, 5.00, NULL, '2025-10-23 18:51:32'),
(3169, 23, 95, 0, 1, NULL, 10.00, '2025-10-25 22:08:54'),
(3171, 23, 95, 1, 0, 5.00, NULL, '2025-10-26 11:17:02'),
(3173, 23, 96, 1, 0, 6.00, NULL, '2025-10-26 11:18:36'),
(3178, 44, 286, 1, 0, 5.00, NULL, '2025-10-26 12:11:39'),
(3184, 23, 96, 1, 0, 5.00, NULL, '2025-10-27 21:24:27'),
(3185, 23, 95, 1, 0, 2.00, NULL, '2025-10-27 22:46:19');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_data_criacao` (`data_criacao`),
  ADD KEY `idx_acao` (`acao`);

--
-- Índices de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_plano` (`id_plano`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `status` (`status`),
  ADD KEY `data_fim` (`data_fim`),
  ADD KEY `idx_assinaturas_usuario_status` (`id_usuario`,`status`);

--
-- Índices de tabela `cartoes_salvos`
--
ALTER TABLE `cartoes_salvos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `controle`
--
ALTER TABLE `controle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuario_controle` (`id_usuario`);

--
-- Índices de tabela `mentores`
--
ALTER TABLE `mentores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentores_ibfk_1` (`id_usuario`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD KEY `nome_2` (`nome`);

--
-- Índices de tabela `transacoes_mercadopago`
--
ALTER TABLE `transacoes_mercadopago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_pago_mercadopago` (`id_pago_mercadopago`),
  ADD KEY `id_assinatura` (`id_assinatura`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `status_pagamento` (`status_pagamento`),
  ADD KEY `id_pago_mercadopago_2` (`id_pago_mercadopago`),
  ADD KEY `idx_transacoes_usuario` (`id_usuario`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuarios_id_plano` (`id_plano`),
  ADD KEY `idx_usuarios_status_assinatura` (`status_assinatura`);

--
-- Índices de tabela `valor_mentores`
--
ALTER TABLE `valor_mentores`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cartoes_salvos`
--
ALTER TABLE `cartoes_salvos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `controle`
--
ALTER TABLE `controle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1037;

--
-- AUTO_INCREMENT de tabela `mentores`
--
ALTER TABLE `mentores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=314;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `transacoes_mercadopago`
--
ALTER TABLE `transacoes_mercadopago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de tabela `valor_mentores`
--
ALTER TABLE `valor_mentores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3198;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `fk_admin_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD CONSTRAINT `assinaturas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assinaturas_ibfk_2` FOREIGN KEY (`id_plano`) REFERENCES `planos` (`id`);

--
-- Restrições para tabelas `cartoes_salvos`
--
ALTER TABLE `cartoes_salvos`
  ADD CONSTRAINT `cartoes_salvos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `controle`
--
ALTER TABLE `controle`
  ADD CONSTRAINT `fk_usuario_controle` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `mentores`
--
ALTER TABLE `mentores`
  ADD CONSTRAINT `mentores_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `transacoes_mercadopago`
--
ALTER TABLE `transacoes_mercadopago`
  ADD CONSTRAINT `transacoes_mercadopago_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transacoes_mercadopago_ibfk_2` FOREIGN KEY (`id_assinatura`) REFERENCES `assinaturas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
