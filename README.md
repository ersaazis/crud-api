composer config repositories.crud-api git https://github.com/ersaazis/crud-api.git

composer require ersaazis/crudapi


bootstrap/app.php

$app->register(ersaazis\crudapi\ServiceProvider::class);
