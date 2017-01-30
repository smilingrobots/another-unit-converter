# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  config.vm.box = "bento/centos-7.2"

  config.vm.provider :virtualbox do |v|
    v.customize ["modifyvm", :id, "--memory", 1024]
    v.customize ["modifyvm", :id, "--cpus", 1]
    v.customize ["modifyvm", :id, "--cableconnected1", "on"]
    v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    v.customize ["modifyvm", :id, "--natdnsproxy1", "on"]

    v.name = File.basename(Dir.pwd)
  end

  config.ssh.forward_agent = true

  if defined?(VagrantPlugins::HostsUpdater)
    config.hostsupdater.remove_on_suspend = true
  end

  config.vm.network "private_network", ip: "192.168.13.38"
  config.vm.hostname = "another-unit-converter.dev"

  if ! Dir.exists?(File.join(Dir.pwd, ".site"))
    Dir.mkdir(File.join(Dir.pwd, ".site"))
  end

  config.vm.synced_folder ".site/", "/srv/www/", :mount_options => [ "dmode=777", "fmode=666" ]
  config.vm.provision "shell", path: "scripts/vagrant-provision.sh"

end
