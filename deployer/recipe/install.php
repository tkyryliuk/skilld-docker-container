<?php

namespace Deployer;

// Install site.
task('install:si', function () {
  $stage = get('stage');

  if ($stage != 'dev') {
    return;
  }

  $uid = run('id -u');
  $gid = run('id -g');

  cd('{{docker_config_path}}');
  $in_container = "docker-compose exec -T --user $uid:$gid php";
  run("$in_container chmod +w web/sites/default", ['tty' => TRUE]);
  if (test('[ -f web/sites/default/settings.php ]')) {
    run("$in_container chmod +w web/sites/default/settings.php && $in_container rm sites/default/settings.php");
  }
  run("$in_container cp site-settings/settings.php web/sites/default/settings.php");
  run("$in_container mkdir -p web/sites/default/files");

  // Cleanup database.
  run("$in_container drush sql-drop -y", ['tty' => TRUE]);

  // Import database dump.
  run("$in_container ash -c 'zcat storage/db-dump.sql.gz | drush sqlc'", ['tty' => TRUE]);
});
