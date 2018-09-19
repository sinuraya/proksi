# Proksi  

Features :
 1. JWT 
 2. RBAC

Powered By:
 1. Slim Framework 3.0
 2. Zend Diactoros
 3. Yii 2 RBAC
 4. Spot Orm

### To deploy : 
 1. Install Composer 
   
    Download and install composer from https://getcomposer.org/download/
 2. Install packages using command below

    ```
    $ composer install
    ```
 3. Make sure you have environment needed by this application by setting the .env file.
 4.  Run migration file using commands below:
     ```
     $ cd bin
     $ php db migrate
     ```
 5. Execute the following command to start the application
    ```
    $ php -S localhost:8080
    ```