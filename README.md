#VIS ( "Vagrant Integration System" )

## Introduction

VIS is a handy utility that lets web developers bootstrap a VM through Vagrant via Chef. This utility script will handle the grunt-work when you need to integrate Vagrant into a project of your choice. After a series of prompts at command-line, VIS generates an entire Vagrant scaffold and even a dynamic, custom provisioning cookbook to get you on your feet.

## Requirements

1. PHP-CLI
1. [Vagrant] installed

## Installation

1. `git clone git@github.com:carldanley/VIS.git` - clone [VIS] somewhere on your computer.
1. Add a function named `vis_integrate` to your `~/.bash_profile` that calls the VIS.php script you just cloned like this `php /my/dir/VIS/VIS.php $PWD --integrate`
1. Don't forget to `source ~/.bash_profile`
1. Navigate to a directory of your choice and type `vis_integrate`
1. Answer any questions VIS might have
1. Watch as VIS integrates Vagrant right into your project for you
1. When VIS is finished and you've returned to command-line, you can immediately `vagrant up` to boot-up your brand new VM. It's that easy!
1. Don't forget to add any hosts records to your `/etc/hosts` file.

## MySQL Imports

You can easily import all of your *.sql files by creating a directory within the root of your project named `db-imports`. After doing so, simply copy your *.sql files into the `db-imports` folder. When you run VIS, it will detect these SQL files and prompt you to load them when the VM starts. Be sure to name the SQL files with the name of the database that you would like to import these files into.

## What you Get

After VIS has successfully completed, you'll have a bootstrapped VM running the following:
* Ubuntu 12.04
* Nginx
* PHP5-FPM
* MySQL
I'm currently working on providing a way to integrate other packages like Xdebug, PHPUnit, and WP CLI into the server based on preference. Stay tuned for updates!

[Vagrant]: http://vagrantup.com
[VIS]: http://github.com/carldanley/VIS