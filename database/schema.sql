-- =====================================================
-- SQL Schema for App Models
-- =====================================================

-- 1. Create flow table (Workflow model)
-- Corresponds to: app/Models/Workflow.php
CREATE TABLE IF NOT EXISTS `flow` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Workflow name',
    `description` TEXT NULL COMMENT 'Workflow description',
    `definition` TEXT NULL COMMENT 'Workflow definition in JSON format',
    `nodes` VARCHAR(1000) NOT NULL DEFAULT '' COMMENT 'Comma-separated list of node IDs',
    `status` INT NOT NULL DEFAULT 0 COMMENT 'Workflow status: 0=draft, 1=pending, 2=active, 3=completed, 4=failed',
    `stage` INT NOT NULL DEFAULT 0 COMMENT 'Current workflow node index (0-based), indicating which node the workflow is currently at',
    `ct` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create timestamp, in milliseconds since epoch',
    `ut` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Update timestamp, in milliseconds since epoch',
    PRIMARY KEY (`id`),
    INDEX `idx_flow_status` (`status`),
    INDEX `idx_flow_stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='work flow';

-- 2. Create node table (WorkNode model)
-- Corresponds to: app/Models/WorkNode.php
CREATE TABLE IF NOT EXISTS `node` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Node name',
    `description` TEXT NULL COMMENT 'Node description',
    `node_type` INT NOT NULL DEFAULT 0 COMMENT 'Type of node: 0=task, 1=decision',
    `resources` VARCHAR(1000) NOT NULL DEFAULT '' COMMENT 'Resources required for the node, comma-separated',
    `actions` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Comma-separated list of actions for the node, 0=reject, 1=approve',
    `ct` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create timestamp, in milliseconds since epoch',
    `ut` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Update timestamp, in milliseconds since epoch',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='work node';

-- 3. Create resource meta table
CREATE TABLE `resource_meta` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'resource meta name' COLLATE 'utf8mb4_unicode_ci',
    `description` TEXT NULL DEFAULT NULL COMMENT 'resource meta description' COLLATE 'utf8mb4_unicode_ci',
    `code` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'resource type code' COLLATE 'utf8mb4_unicode_ci',
    `ct` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Create timestamp, in milliseconds since epoch',
    `ut` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Update timestamp, in milliseconds since epoch',
    PRIMARY KEY (`id`),
    INDEX `uni_resource_meta_code` (`code`)
) COMMENT='resource meta information'
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
ROW_FORMAT=DYNAMIC
;
