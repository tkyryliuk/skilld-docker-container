<?php
namespace Deployer;

require 'recipe/common.php';
require 'deployer/recipe/install.php';
require 'deployer/recipe/prepare.php';
require 'deployer/recipe/deploy.php';
require 'deployer/recipe/exec.php';

inventory('deployer/hosts.yml');

set('repository', 'git@bitbucket.org:change_me/change_me.git');
set('default_stage', 'dev');
set('image_php', 'skilldlabs/php:71-fpm');
set('compose_file', './docker/docker-compose.yml:./docker/docker-compose.override.yml');
set('project_namespace', 'change_me');
set('image_php', 'skilldlabs/php:71-fpm');

task('reinstall', [
  'prepare:set_environment',
  'prepare:clean',
  'deploy:vendors',
  'prepare:up',
  'prepare:deps',
  'deploy:chown',
  'install:si',
  'deploy:drupal_deploy',
  'deploy:info',
]);

task('clean', [
  'prepare:set_environment',
  'prepare:clean',
]);
