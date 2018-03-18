<?php

namespace Deployer;


// Create db backup.
task('prepare:clean', function () {
  $path_current = run('pwd');
  $image_php = get('image_php');

  // todo: allow override this value from deploy.php.
  $clean_dirs = [
    'vendor',
    'web/includes',
    'web/misc',
    'web/modules',
    'web/profiles',
    'web/scripts',
    'web/themes',
    'web/sites/all/modules/contrib',
    'web/sites/default',
  ];

  foreach ($clean_dirs as $dir) {
    if (file_exists($dir)) {
      writeln("Removing $dir");
      run("docker run --rm -v $path_current:/mnt $image_php sh -c \"rm -rf /mnt/$dir\"");
    }
  }
})
  ->local()
  ->addAfter('prepare:down');

// Set compose options.
task('prepare:set_environment', function () {
  if (!has('docker_config_path')) {
    set('docker_config_path', run('pwd') . '/docker');
  }
  if (!has('deploy_path')) {
    set('deploy_path', run('pwd'));
  }
  if (!has('release_path')) {
    set('release_path', run('pwd'));
  }

  cd('{{docker_config_path}}');

  // Generate .env file.
  if (!test('[ -f .env ]')) {
    $stage = get('stage');
    $image_php = get('image_php');
    $project_namespace = get('project_namespace');
    $compose_project_name = $project_namespace . $stage;

    // DOMAIN_NAME.
    $domain_name = 'undefined';
    if (has('domain')) {
      $domain_name = get('domain');
    }

    // IPRANGE.
    $compose_net_name = strtolower($compose_project_name) . '_front';
    $compose_net_name = preg_replace('/[^A-Za-z0-9\-]/', '', $compose_net_name);
    // Try to create network if there is no any.
    if (empty(run("docker network ls -q -f Name=$compose_net_name"))) {
      if (!test("docker network create $compose_net_name")) {
        writeln('Error creating docker network! Please set IPRAGE manually.');
        $ip_range = 'undefined';
      }
    }
    if (!isset($ip_range)) {
      $ip_range = run("docker network inspect $compose_net_name --format '{{(index .IPAM.Config 0).Subnet}}'");
      run("docker network rm $compose_net_name");
    }

    run("
      echo \"COMPOSE_PROJECT_NAME=$compose_project_name\" > .env
      echo \"DOMAIN_NAME=$domain_name\" >> .env
      echo \"IMAGE_PHP=$image_php\" >> .env
      echo \"DEPLOY_ENV=$stage\" >> .env
      echo \"IPRANGE=$ip_range\" >> .env
    ");
  }
})->once();

// Launch site again.
task('prepare:down', function () {
  cd('{{docker_config_path}}');
  run('docker-compose down -v --remove-orphans', ['tty' => TRUE]);
});

// Launch site again.
task('prepare:up', function () {
  cd('{{docker_config_path}}');
  run('docker-compose up -d', ['tty' => TRUE]);
});

// Launch site again.
task('prepare:deps', function () {
  $uid = run('id -u');
  $gid = run('id -g');

  cd('{{docker_config_path}}');
  run("docker-compose exec -T --user $uid:$gid php composer global require -o --update-no-dev --no-suggest \"hirak/prestissimo:^0.3\"", ['tty' => TRUE]);
});
