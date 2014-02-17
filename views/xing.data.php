<div class="row">
    <div class="btn-group">
        <a href="/index.php?form=xing" class="btn btn-primary">
            <i class="glyphicon glyphicon-chevron-left"></i> Neue Suche</a>
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12"><h3>Gefundenes Profil: <?php echo $result->display_name; ?></h3></div>
</div>
<div class="row">
    <div><img src="<?=$data['photo']?>"></div>
</div>
<? foreach($data as $key => $row){ ?>
<div class="row">
    <div class="col-md-1"><input type="checkbox" name="<?=$key?>" value="<?=(json_encode($row['value']))?>"></div>
    <div class="col-md-4"><?=$row['label']?></div>
    <div class="col-md-7"><?=$row['value']?></div>
</div>
<? } ?>