#  ContentKing for Conductor WordPress Plugin

- https://www.contentkingapp.com/support/wordpress-plugin/
- https://wordpress.org/plugins/contentking/

## Local development & testing with Varying Vagrant Vagrants

This section will help you with setup local development environment.

### Required Software
- [Vagrant](https://www.vagrantup.com/) 2.2.4+
- [VirtualBox](https://www.virtualbox.org/) 5.2+

### Instalation
- Install Vagrant Hosts Updater package 
  ```
  vagrant plugin install vagrant-hostsupdater
  ```
- Clone repository `Varying-Vagrant-Vagrants/VVV` into this folder (the folder `VVV` is ignored in `.gitignore`)
  ```
  git clone https://github.com/Varying-Vagrant-Vagrants/VVV.git
  ```
- Link file **vvv-custom.yml** into `VVV` folder 
  ```
  cd VVV && ln -s ../vvv-custom.yml vvv-custom.yml
  ```
- Run Vagrant to provision VirtualBox machine and setup defined WordPress instances
  ```
  cd VVV && vagrant up
  ```

### Help

Full documentation of Varying Vagrant Vagrant is available [here](https://varyingvagrantvagrants.org/).

- Default credentials are listed [here](https://varyingvagrantvagrants.org/docs/en-US/default-credentials/)
- It's necessary to reprovision the virtual machine when file `vvv-custom.yml` has been changed
  ```
  cd VVV && vagrant reload --provision
  ```  
  
  
