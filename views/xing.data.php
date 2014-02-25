<div class="row">
    <div class="btn-group">
        <a href="/index.php?form=xing" class="btn btn-primary">
            <i class="glyphicon glyphicon-chevron-left"></i> Neue Suche
        </a>
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12"><h3>Gefundenes Profil: <?php echo $result->display_name; ?></h3></div>
</div>
<form action="/index.php" method="post">
    <input type="hidden" name="form" value="xing">
    <input type="hidden" name="action" value="import">
    <input type="hidden" name="fmid" value="<?=$fmID?>">
    <div class="row">
        <div class="col-md-9">
            <div class="btn-group">
                <a class="btn btn-default jsSelectAll">Alle</a>
                <a class="btn btn-default jsSelectNone">Keine</a>
                <br>
                <br>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">
                <i class="glyphicon glyphicon-chevron-right"></i> Importieren
            </button>
        </div>
    </div>
    <div class="form-data">
        <? foreach($data as $key => $row){ ?>
            <div class="row">
                <div class="col-1">
                    <input type="checkbox" name="<?=$key?>" value="yes" class="jsCheckbox">
                </div>
                <div class="col-md-3"><?=$row['label']?></div>
                <div class="col-md-8"><?=$row['value']?></div>
            </div>
        <? } ?>
    </div>
    <div class="row">
        <br>
        <div class="col-md-9">
            <div class="btn-group">
                <a class="btn btn-default jsSelectAll">Alle</a>
                <a class="btn btn-default jsSelectNone">Keine</a>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">
                <i class="glyphicon glyphicon-chevron-right"></i> Importieren
            </button>
        </div>
    </div>
</form>
<script type="text/javascript">
    $(document).ready(function(){
        $(".jsSelectAll").click(function(event){
            $(".jsCheckbox").each(function(){
                var checkbox = $(this);
                checkbox.prop("checked", true);
            });

        });

        $(".jsSelectNone").click(function(event){
            $(".jsCheckbox").each(function(){
                var checkbox = $(this);
                checkbox.prop("checked", false);
            });

        });
    });
</script>
