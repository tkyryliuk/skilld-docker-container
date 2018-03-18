<?php

namespace Deployer;

// Enter php container.
task('exec', function () {
  if (!has('docker_config_path')) {
    set('docker_config_path', run('pwd') . '/docker');
  }
  $uid = run('id -u');
  $gid = run('id -g');

  cd('{{docker_config_path}}');
  run("docker-compose exec --user $uid:$gid php ash", ['tty' => TRUE]);
});

// Enter php container as root.
task('exec0', function () {
  if (!has('docker_config_path')) {
    set('docker_config_path', run('pwd') . '/docker');
  }

  cd('{{docker_config_path}}');
  run("docker-compose exec php ash", ['tty' => TRUE]);
});
