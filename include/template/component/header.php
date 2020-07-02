<nav class="navbar navbar-default">
    <div class="row col-md-12">
        <div class="col-md-6">
            <p class="navbar-text signed">Signed in as <?php echo $data['admin_id']; ?></p>
        </div>
        <div class="col-md-6">
            <a class="navbar-text navbar-right" href="index.php?logout" title="Logout">
                <button class="btn btn-danger">Logout <span class="glyphicon glyphicon-off"
                                                            aria-hidden="true"></span></button>
            </a>
            <a class="navbar-text navbar-right" href="index.php" title="Configuration">
                <button class="btn btn-default">Configurations</button>
            </a>
        </div>
    </div>
</nav>