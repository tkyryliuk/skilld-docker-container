<?php

namespace Deployer;


desc('Installing vendors');
task('deploy:vendors', function () {
  if (!commandExist('unzip')) {
    writeln('<comment>To speed up composer installation setup "unzip" command with PHP zip extension https://goo.gl/sxzFcD</comment>');
  }

  run('cd {{release_path}} && {{bin/composer}} {{composer_options}}');
});

// Set correct permissions to files directory.
task('deploy:chown', function () {
  if (!has('docker_config_path')) {
    set('docker_config_path', run('pwd') . '/docker');
  }

  cd('{{docker_config_path}}');

  $uid = run('id -u');
  $gid = run('id -g');

  test("docker-compose exec -T php chown $uid:$gid /var/www/html/web -R");
  test('docker-compose exec -T php chown www-data: /var/www/html/web/sites/default/files -R');
});

// Launch site again.
task('deploy:drupal_deploy', function () {
  $uid = run('id -u');
  $gid = run('id -g');

  cd('{{docker_config_path}}');
  $in_container = "docker-compose exec -T --user $uid:$gid php";

  // Deploy drupal.
  run("$in_container drush updb -y && drush fra -y && drush cc all", [
    'timeout' => NULL,
    'tty' => TRUE,
  ]);
  // Run deployment on development instance.
  if (get('stage') == 'dev') {
    run("$in_container drush sql-query 'update users set name=\"admin\" where uid=1'", [
      'timeout' => NULL,
      'tty' => TRUE,
    ]);
    run("$in_container drush upwd admin --password='admin'", [
      'timeout' => NULL,
      'tty' => TRUE,
    ]);
    run("$in_container drush en -y dblog stage_file_proxy && drush variable-set stage_file_proxy_origin 'http://agroscience.com.ua'", [
      'timeout' => NULL,
      'tty' => TRUE,
    ]);
  }
});

task('info', function () {
  if (!has('docker_config_path')) {
    set('docker_config_path', run('pwd') . '/docker');
  }

  $stage = get('stage');
  $project_namespace = get('project_namespace');
  $compose_project_name = $project_namespace . $stage;
  cd('{{docker_config_path}}');
  foreach (['web', 'mail'] as $service) {
    $port = '';
    if ($service == 'mail') {
      $port = ':8025';
    }
    if (run("docker inspect --format=\"\{\{ .State.Running \}\}\" {$compose_project_name}_{$service}")) {
      $ip = run("docker inspect {$compose_project_name}_{$service} | grep -w \"IPAddress\" | awk '{ print $2 }' | tail -n 1 | cut -d \",\" -f1");
      $ip = str_replace('"', '', $ip);
      writeln("$service IP: $ip$port");
    }
  }
});
