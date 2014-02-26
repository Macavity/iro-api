<h1>Willkommen <?=$userName?></h1>
<form action="/index.php" method="get">
    <input type="hidden" name="form" value="xing">
    <input type="hidden" name="fmid" value="<?=$fmID?>">
    <div class="form-horizontal">
        <div class="col-md-3 col-md-offset-1">
            <label for="xinglink">Link zum Xing-Profil</label>
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" id="xinglink" name="xinglink" placeholder="Link eintragen">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">
                Weiter
                <i class="glyphicon glyphicon-chevron-right"></i>
            </button>
        </div>
    </div>
</form>