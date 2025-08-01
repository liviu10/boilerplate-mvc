<?php

    use LiviuVoica\BoilerplateMVC\Controllers\UserController;

    $container = require __DIR__ . '/src/bootstrap/app.php';
    $controller = $container->get(UserController::class);

    $data = (new UserController())->index();

?>

<!doctype html>
<html lang="en">
    <?php include 'src/components/header.php' ?>

    <body>

        <div class="container">
            <h1>
                <?php echo htmlspecialchars($data['title']); ?>
            </h1>
        </div>

        <?php include 'src/components/javascript.php' ?>
    </body>

</html>