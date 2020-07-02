<textarea class="form-control" data-config-file="<?php echo $data['file']; ?>"
          name="" id="" cols="30" rows="20"><?php echo $data['content']; ?></textarea>

<h3>History</h3>
<div class="panel-group" id="accordion-<?php echo $data['file']; ?>">
    <?php foreach ($data['history'] as $i => $file): ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion-<?php echo $data['file']; ?>"
                       href="#collapse-<?php echo $data['file']; ?>-<?php echo $i; ?>">
                        <?php echo $file['name']; ?>
                    </a>
                </h4>
            </div>
            <div id="collapse-<?php echo $data['file']; ?>-<?php echo $i; ?>"
                 class="panel-collapse collapse">
                <div class="panel-body">
                    <pre><?php echo $file['content'] ?></pre>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>