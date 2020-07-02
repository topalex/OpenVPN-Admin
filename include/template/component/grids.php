<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#menu0"><span class="glyphicon glyphicon-user"
                                                                aria-hidden="true"></span> OpenVPN Users</a></li>
    <li><a data-toggle="tab" href="#menu1"><span class="glyphicon glyphicon-book" aria-hidden="true"></span> OpenVPN
            logs</a></li>
    <li><a data-toggle="tab" href="#menu2"><span class="glyphicon glyphicon-king" aria-hidden="true"></span> Web Admins</a>
    </li>
    <li><a data-toggle="tab" href="#menu3"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span> Configs</a>
    </li>
</ul>
<div class="tab-content">

    <div id="menu0" class="tab-pane fade in active">
        <!-- Users grid -->
        <div class="block-grid row" id="user-grid">
            <h4>
                OpenVPN Users
                <button data-toggle="modal" data-target="#modal-user-add" type="button" class="btn btn-success btn-xs">
                    <span class="glyphicon glyphicon-plus"></span></button>
            </h4>
            <table id="table-users" class="table"></table>

            <div id="modal-user-add" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Add user</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="modal-user-add-username">Username</label>
                                <input type="text" name="username" id="modal-user-add-username" class="form-control"
                                       autofocus/>
                            </div>
                            <div class="form-group">
                                <label for="modal-user-add-password">Password</label>
                                <input type="password" name="password" id="modal-user-add-password"
                                       class="form-control"/>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="modal-user-add-save">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="menu1" class="tab-pane fade">
        <!-- Logs grid -->
        <div class="block-grid row" id="log-grid">
            <h4>
                OpenVPN logs
            </h4>
            <table id="table-logs" class="table" data-filter-control="true"></table>
        </div>
    </div>

    <div id="menu2" class="tab-pane fade">
        <!-- Admins grid -->
        <div class="block-grid row" id="admin-grid">
            <h4>
                Web Admins
                <button data-toggle="modal" data-target="#modal-admin-add" type="button" class="btn btn-success btn-xs">
                    <span class="glyphicon glyphicon-plus"></span></button>
            </h4>
            <table id="table-admins" class="table"></table>

            <div id="modal-admin-add" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Add admin</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="modal-admin-add-username">Username</label>
                                <input type="text" name="username" id="modal-admin-add-username" class="form-control"
                                       autofocus/>
                            </div>
                            <div class="form-group">
                                <label for="modal-admin-add-password">Password</label>
                                <input type="password" name="password" id="modal-admin-add-password"
                                       class="form-control"/>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="modal-admin-add-save">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="menu3" class="tab-pane fade">
        <!-- configs -->
        <div class="block-grid row" id="config-cards">
            <ul class="nav nav-tabs nav-tabs-justified">
                <li class="active">
                    <a data-toggle="tab" href="#menu-1-0" data-config-file="gnu-linux">
                        <span class="glyphicon glyphicon-heart" aria-hidden="true"></span> Linux
                    </a>
                </li>
                <li>
                    <a data-toggle="tab" href="#menu-1-1" data-config-file="windows">
                        <span class="glyphicon glyphicon-th-large" aria-hidden="true"></span> Windows
                    </a>
                </li>
                <li>
                    <a data-toggle="tab" href="#menu-1-2" data-config-file="osx">
                        <span class="glyphicon glyphicon-apple" aria-hidden="true"></span> OSX
                    </a>
                </li>

                <li id="save-config-btn" class="pull-right hidden"><a class="progress-bar-striped" href="#"><span
                                class="glyphicon glyphicon-save" aria-hidden="true"></span></a></li>
            </ul>
            <div class="tab-content">
                <div id="menu-1-0" class="tab-pane fade in active"></div>
                <div id="menu-1-1" class="tab-pane fade in"></div>
                <div id="menu-1-2" class="tab-pane fade in"></div>
            </div>
        </div>
    </div>

</div>

<script src="vendor/jquery/dist/jquery.min.js"></script>
<script src="vendor/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="vendor/bootstrap-table/dist/bootstrap-table.min.js"></script>
<script src="vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<script src="vendor/bootstrap-table/dist/extensions/editable/bootstrap-table-editable.min.js"></script>
<script src="vendor/bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.js"></script>
<script src="vendor/bootstrap-table/dist/extensions/sticky-header/bootstrap-table-sticky-header.min.js"></script>
<script src="vendor/bootstrap-table/dist/extensions/auto-refresh/bootstrap-table-auto-refresh.min.js"></script>
<script src="vendor/x-editable/dist/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
<script src="js/grids.js"></script>
