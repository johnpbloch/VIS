<?php
/**
 * Original Filename: class-vis-templates.php
 * User: carldanley
 * Created on: 1/28/13
 * Time: 1:50 AM
 */

class VIS_Templates {

	/**
	 * Blank constructor
	 */
	public function __construct() {}

	/**
	 * Renders the vagrant file used for integration of vagrant into the project
	 *
	 * @param array $settings The settings container generated from the VIS_Action::integrate command
	 * @return string The contents of the newly rendered Vagrantfile
	 */
	public static function render_vagrant_file( $settings = array() ) {
		$lines = array();
		$lines[] = '# -*- mode: ruby -*-';
		$lines[] = '# vi: set ft=ruby :' . "\n";
		$lines[] = 'Vagrant::Config.run do |config|';
		$lines[] = '	config.vm.box = "precise32"';
		//$lines[] = '	config.vm.provision :shell, :inline => "git submodule foreach git pull origin master"';
		$lines[] = '	config.vm.box_url = "http://files.vagrantup.com/precise32.box"';
		$lines[] = '	config.vm.host_name = "10up"';
		$lines[] = '	config.vm.network :hostonly, "42.42.42.42"'; // the answer to the universe is "42"
		$lines[] = '	config.vm.forward_port 80, 8080, :auto => true';
		$lines[] = '	config.vm.share_folder "default", "/var/www/", "' . $settings[ 'base-dir' ] . '", :nfs => true';
		$lines[] = '	config.ssh.forward_agent = true' . "\n";
		$lines[] = '	config.vm.provision :chef_solo do |chef|';
		$lines[] = '		chef.cookbooks_path = "cookbooks"';
		$lines[] = '		chef.json = {';
		$lines[] = '			"nginx" => {';
		$lines[] = '				"default_site_enabled" => false';
		$lines[] = '			},';
		$lines[] = '			"mysql" => {';
		$lines[] = '				"server_root_password" => "root",';
		$lines[] = '				"server_debian_password" => "root",';
		$lines[] = '				"server_repl_password" => "root"';
		$lines[] = '			}';
		$lines[] = '		}';

		foreach( $settings[ 'cookbooks' ] as $cookbook_recipe => $cookbook_settings )
			$lines[] = '		chef.add_recipe( "' . $cookbook_recipe . '" )';

		$lines[] = '		chef.add_recipe( "custom-provisions" )';

		$lines[] = '	end';
		$lines[] = 'end';

		return implode( "\n", $lines );
	}

	/**
	 * Renders the default.rb recipe file for the custom provisioning cookbook
	 *
	 * @param array $settings The settings container generated from the VIS_Action::integrate command
	 * @return string The contents of the newly rendered default.rb recipe
	 */
	public static function render_custom_provisioning_recipe( $settings = array() ) {
		$lines = array();
		$lines[] = 'include_recipe "nginx"';
		$lines[] = 'include_recipe "php-fpm"';
		$lines[] = 'cookbook_file "#{node[:nginx][:dir]}/sites-available/' . $settings[ 'domain-name' ] . '" do';
		$lines[] = '	source "' . $settings[ 'domain-name' ] . '"';
		$lines[] = '	mode 0644';
		$lines[] = 'end';
		$lines[] = 'nginx_site "' . $settings[ 'domain-name' ] . '" do';
		$lines[] = '	action :enable';
		$lines[] = 'end' . "\n";

		// add all sub domains to this file as well
		if( $settings[ 'has-sub-domains' ] ) {
			foreach( $settings[ 'sub-domains' ] as $sub_domain ) {
				$sub_domain = $sub_domain . '.' . $settings[ 'domain-name' ];
				$lines[] = 'cookbook_file "#{node[:nginx][:dir]}/sites-available/' . $sub_domain . '" do';
				$lines[] = '	source "' . $sub_domain . '"';
				$lines[] = '	mode 0644';
				$lines[] = 'end';
				$lines[] = 'nginx_site "' . $sub_domain . '" do';
				$lines[] = '	action :enable';
				$lines[] = 'end' . "\n";
			}
		}

		// restart nginx and php5-fpm
		$lines[] = 'service "nginx" do';
		$lines[] = '	action :restart';
		$lines[] = 'end';
		$lines[] = 'service "php5-fpm" do';
		$lines[] = '	action :restart';
		$lines[] = 'end';

		// add all the import commands as well
		if( $settings[ 'has-mysql-imports' ] ) {
			$lines[] = 'directory "/mysql-db-imports/" do';
			$lines[] = '	owner "root"';
			$lines[] = '	group "root"';
			$lines[] = '	mode 00755';
			$lines[] = '	recursive true';
			$lines[] = 'end' . "\n";

			foreach( $settings[ 'mysql-imports' ] as $import_file ) {
				$import_file = VIS_System::extract_filename( $import_file );

				// remove the extension on this filename
				$db_name = explode( '.', $import_file );
				array_pop( $db_name );
				$db_name = implode( '.', $db_name );

				$lines[] = 'cookbook_file "/mysql-db-imports/' . $import_file . '" do';
				$lines[] = '	mode 0644';
				$lines[] = '	source "' . $import_file . '"';
				$lines[] = 'end';

				$create_command = 'create database if not exists \\\`' . $db_name . '\\\`;';
				$grant_command = 'grant all on \\\`' . $db_name . '\\\`.* to \'root\'@\'localhost\' identified by \'#{node[:mysql][:server_root_password]}\';';

				$lines[] = 'execute "mysql -u root -p#{node[:mysql][:server_root_password]} -e \"' . $create_command . ' ' . $grant_command . '\""';
				$lines[] = 'execute "mysql -u root -p#{node[:mysql][:server_root_password]} ' . $db_name . ' < /mysql-db-imports/' . $import_file . '"' . "\n";
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Renders an Nginx Site Configuration for the specified domain name.
	 *
	 * @param string $domain_name The domain name ( including subdomain, if necessary ) that we're creating an nginx configuration for
	 * @param bool $is_wordpress_site Indicates whether or not this is a WordPress site entry
	 * @return string The newly rendered nginx site configuration
	 */
	public static function render_nginx_site_configuration( $domain_name = '', $is_wordpress_site = true ) {
		$lines = array();
		$lines[] = 'server {';
		$lines[] = '	listen 80;';
		$lines[] = '	server_name ' . $domain_name . ';';
		$lines[] = '	root /var/www/;' . "\n";
		$lines[] = '	# Directives to send expires headers and turn off 404 error logging.';
		$lines[] = '	location ~* \.(js|css|png|jpg|jpeg|gif|ico)$ {';
		$lines[] = '		expires 24h;';
		$lines[] = '		log_not_found off;';
		$lines[] = '	}' . "\n";
		$lines[] = '	# this prevents hidden files (beginning with a period) from being served';
		$lines[] = '	location ~ /\. {';
		$lines[] = '		access_log off;';
		$lines[] = '		log_not_found off;';
		$lines[] = '		deny all;';
		$lines[] = '	}' . "\n";

		if( $is_wordpress_site ) {
			$lines[] = '	location / {';
			$lines[] = '		index index.php;';
			$lines[] = '		try_files $uri $uri/ /index.php?$args;';
			$lines[] = '	}' . "\n";
			$lines[] = '	# Add trailing slash to */wp-admin requests.';
			$lines[] = '	rewrite /wp-admin$ $scheme://$host$uri/ permanent;' . "\n";
			$lines[] = '	# Pass uploaded files to wp-includes/ms-files.php.';
			$lines[] = '	rewrite /files/$ /index.php last;' . "\n";
			$lines[] = '	if ($uri !~ wp-content/plugins) {';
			$lines[] = '		rewrite /files/(.+)$ /wp-includes/ms-files.php?file=$1 last;';
			$lines[] = '	}' . "\n";
			$lines[] = '	# Rewrite multisite \'.../wp-.*\' and \'.../*.php\'.';
			$lines[] = '	if (!-e $request_filename) {';
			$lines[] = '		rewrite ^/[_0-9a-zA-Z-]+(/wp-.*) $1 last;';
			$lines[] = '		rewrite ^/[_0-9a-zA-Z-]+.*(/wp-admin/.*\.php)$ $1 last;';
			$lines[] = '		rewrite ^/[_0-9a-zA-Z-]+(/.*\.php)$ $1 last;';
			$lines[] = '	}' . "\n";
		}
		else {
			$lines[] = '	location / {';
			$lines[] = '		index index.php;';
			$lines[] = '	}' . "\n";
		}

		$lines[] = '	location ~ \.php$ {';
		$lines[] = '		client_max_body_size 25M;';
		$lines[] = '		try_files $uri =404;';
		$lines[] = '		fastcgi_pass   127.0.0.1:9000;';
		$lines[] = '		fastcgi_index  index.php;';
		$lines[] = '		include /etc/nginx/fastcgi_params;';
		$lines[] = '	}' . "\n";

		$lines[] = '}';

		return implode( "\n", $lines );
	}

}