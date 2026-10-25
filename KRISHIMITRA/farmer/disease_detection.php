<?php
require_once '../config/database.php';
if (!isLoggedIn('farmer')) { redirect('farmer_login.php'); }
// config/database.php creates a PDO connection named $pdo.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('PDO database connection not found. Check config/database.php.');
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function advisoriesFor(PDO $pdo, string $crop): array {
    $sql = "SELECT advisory_id,crop,issue_name,cause,symptoms,first_steps,prevention,caution
            FROM pest_advisories
            WHERE crop = :crop OR crop = 'Any'
            ORDER BY CASE WHEN crop = 'Any' THEN 1 ELSE 0 END, issue_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['crop' => $crop]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['symptoms_array'] = json_decode($row['symptoms'], true) ?: [];
    }
    unset($row);

    return $rows;
}
$crops=['Rice','Potato','Tomato','Wheat','Cotton','Sugarcane','Groundnut','Maize','Bajra','Mustard','Chilli','Other'];
$symptoms=[
 'leaf spots'=>'Spots on leaves','brown spots'=>'Brown spots','yellow spots'=>'Yellow spots','rusty pustules'=>'Rust-coloured pustules','leaf discoloration'=>'Leaf colour change','wilting'=>'Plant wilting','dark lesions'=>'Dark patches','holes in bolls'=>'Holes in bolls/fruits','chewed leaves'=>'Leaves eaten / holes','frass'=>'Insect droppings near damage','dropped bolls'=>'Bolls falling','dead heart'=>'Central shoot drying','shot holes'=>'Small holes in leaves','bore holes'=>'Holes in stem/shoot','stunted growth'=>'Stunted growth','yellow halos'=>'Yellow ring around spots','premature defoliation'=>'Leaves falling early','honeydew'=>'Sticky substance on leaves','sticky leaves'=>'Sticky leaves','curled leaves'=>'Curled leaves','black sooty mold'=>'Black coating on leaves','white lesions'=>'Whitish lesions','neck rot'=>'Neck/upper stem rot'
];
$crop=trim($_POST['crop']??''); $chosen=isset($_POST['symptoms'])?(array)$_POST['symptoms']:[];
$chosen=array_values(array_intersect(array_keys($symptoms),$chosen)); $results=[]; $error=''; $submitted=$_SERVER['REQUEST_METHOD']==='POST';
$photoUrl='';
if($submitted){
 if(!in_array($crop,$crops,true)) $error='Please select a crop.';
 // Photo is optional and is displayed as part of the farmer's report; it is not AI-analyzed.
 if(!$error && isset($_FILES['plant_photo']) && $_FILES['plant_photo']['error']!==UPLOAD_ERR_NO_FILE){
  $f=$_FILES['plant_photo'];
  if($f['error']!==UPLOAD_ERR_OK) $error='Photo upload failed. Please try again.';
  elseif($f['size']>5*1024*1024) $error='Photo must be 5 MB or smaller.';
  else{
   $info=@getimagesize($f['tmp_name']); $mime=$info['mime']??''; $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
   if(!$info||!isset($types[$mime])) $error='Upload a valid JPG, PNG, or WEBP image.';
   else{
    $dir=__DIR__.'/uploads/pest_reports/'; if(!is_dir($dir)) mkdir($dir,0755,true);
    $name=bin2hex(random_bytes(16)).'.'.$types[$mime];
    if(move_uploaded_file($f['tmp_name'],$dir.$name)) $photoUrl='uploads/pest_reports/'.$name;
    else $error='Could not save photo. Check folder permissions.';
   }
  }
 }
 if(!$error && !$chosen) $error='Please select at least one symptom.';
 if(!$error){
  foreach(advisoriesFor($pdo,$crop) as $a){$n=count(array_intersect($chosen,$a['symptoms_array']));if($n>0)$results[]=['advisory'=>$a,'matches'=>$n];}
  usort($results,fn($a,$b)=>$b['matches']<=>$a['matches']);
 }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pest Detection - KrishiMitra</title><link rel="stylesheet" href="farmer_common.css"><link rel="stylesheet" href="farmer_dashboard.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.pest-layout{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start}.detect-card,.result-card,.ref-card{background:#fff;border:1px solid #e4ebe6;border-radius:14px;padding:22px;box-shadow:0 3px 15px rgba(29,73,42,.04);margin-bottom:16px}.detect-card h3,.ref-card h3{color:#28543a;font-size:16px;margin:0 0 14px}.field{margin:14px 0}.field label,.group-label{display:block;color:#526158;font-size:11px;font-weight:600;margin-bottom:7px}select,input[type=file]{width:100%;padding:10px;border:1px solid #dce5df;border-radius:8px;background:#fff;font-family:inherit}.check-list{display:flex;flex-wrap:wrap;gap:8px}.check-list label{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid #dce5df;border-radius:20px;font-size:10px;color:#59665e;cursor:pointer}.check-list input{accent-color:#23934e}.btn-detect{width:100%;padding:12px;border:0;border-radius:8px;background:#23934e;color:#fff;font-family:inherit;font-weight:600;cursor:pointer}.btn-detect:hover{background:#197d41}.notice{padding:12px;background:#fff7df;border-left:4px solid #d6a323;border-radius:7px;color:#69551f;font-size:11px;line-height:1.6}.err{padding:12px;background:#ffeded;color:#922;border-radius:7px;font-size:12px}.result-card{border-left:4px solid #27ae60}.result-card h4{color:#28543a;font-size:15px;margin:5px 0}.result-card p{color:#65736a;font-size:11px;line-height:1.7}.tag{display:inline-block;padding:4px 9px;border-radius:20px;background:#e7f7ec;color:#258049;font-size:9px;font-weight:600}.ref-item{padding:12px 0;border-bottom:1px solid #edf1ee}.ref-item h4{color:#28543a;font-size:12px;margin:0 0 5px}.ref-item p{font-size:10px;line-height:1.6;color:#65736a;margin:5px 0}.preview{max-width:100%;max-height:220px;border-radius:10px;margin-top:10px}@media(max-width:1000px){.pest-layout{grid-template-columns:1fr}}
</style></head><body>
<div class="navbar"><div class="brand"><img src="../pictures/images/logo1.png" alt="KrishiMitra Logo"><h2>KrishiMitra</h2></div><div class="nav-actions"><span class="farmer-user"><i class="fas fa-user"></i><span><?=h($_SESSION['farmer_name']??'Farmer')?></span></span><a href="farmer_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a><a href="../logout.php" class="logout-link"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a></div></div>
<div class="layout"><aside class="sidebar"><div class="sidebar-heading"><span>FARMER PORTAL</span></div><nav>
<a href="farmer_dashboard.php"><i class="fas fa-house"></i><span>Dashboard</span></a><a href="manage_farms.php"><i class="fas fa-tractor"></i><span>My Farms</span></a><a href="soil_data.php"><i class="fas fa-seedling"></i><span>Soil Data</span></a><a href="crop_recommendation.php"><i class="fas fa-leaf"></i><span>Crop Advisory</span></a><a href="weather.php"><i class="fas fa-cloud-sun"></i><span>Weather</span></a><a href="market_prices.php"><i class="fas fa-indian-rupee-sign"></i><span>Market Prices</span></a><a class="active" href="disease_detection.php"><i class="fas fa-bug"></i><span>Pest Detection</span></a><a href="reports.php"><i class="fas fa-file-lines"></i><span>Reports</span></a></nav></aside>
<main class="content"><div class="page-header"><div><h1><i class="fas fa-bug"></i> Pest &amp; Disease Help</h1><p>Upload a photo, choose your crop and symptoms to find possible guidance.</p></div></div>
<div class="pest-layout"><section><div class="detect-card"><h3><i class="fas fa-camera"></i> Affected plant photo &amp; symptoms</h3><div class="notice">Photo upload lets you attach the image to your report. This version matches the symptoms you select; it does not automatically recognize the photo.</div>
<form method="POST" enctype="multipart/form-data"><div class="field"><label for="crop">Select crop</label><select name="crop" id="crop" required><option value="">Choose crop</option><?php foreach($crops as $c):?><option value="<?=h($c)?>" <?=$crop===$c?'selected':''?>><?=h($c)?></option><?php endforeach;?></select></div>
<div class="field"><label for="plant_photo">Photo (optional, max 5 MB)</label><input type="file" name="plant_photo" id="plant_photo" accept="image/jpeg,image/png,image/webp"><img id="photoPreview" class="preview" alt="Selected photo preview" style="display:none"><small style="font-size:10px;color:#7b877e">JPG, PNG or WEBP. Please take a clear close-up.</small></div>
<div class="field"><span class="group-label">Select all symptoms you observe</span><div class="check-list"><?php foreach($symptoms as $key=>$label):?><label><input type="checkbox" name="symptoms[]" value="<?=h($key)?>" <?=in_array($key,$chosen,true)?'checked':''?>><?=h($label)?></label><?php endforeach;?></div></div>
<button class="btn-detect" type="submit"><i class="fas fa-search"></i> Find Possible Problems &amp; Advice</button></form></div>
<?php if($error):?><div class="err"><?=h($error)?></div><?php endif;?>
<?php if($submitted&&!$error):?><h3 style="color:#28543a;font-size:15px"><i class="fas fa-clipboard-check"></i> Possible matches &amp; advice</h3><?php if($photoUrl):?><div class="detect-card"><strong>Uploaded photo</strong><br><img class="preview" src="<?=h($photoUrl)?>" alt="Uploaded crop photo"></div><?php endif;?>
<?php if($results):foreach($results as $item):$a=$item['advisory'];?><article class="result-card"><span class="tag"><?=$item['matches']?> selected symptom(s) matched</span><h4><?=h($a['issue_name'])?></h4><p><strong>Possible cause:</strong> <?=h($a['cause'])?></p><p><strong>First steps:</strong> <?=h($a['first_steps'])?></p><p><strong>Prevention:</strong> <?=h($a['prevention'])?></p><div class="notice"><strong>Please note:</strong> <?=h($a['caution'])?> Symptom matching is not a confirmed diagnosis.</div></article><?php endforeach;?>
<?php else:?><div class="detect-card">No advisory matched these symptoms. Try selecting other symptoms or contact a local agricultural officer.</div><?php endif;?><?php endif;?></section>
<aside class="ref-card"><h3><i class="fas fa-book-open"></i> Advisory library</h3><p style="font-size:10px;color:#68756c;line-height:1.6">Reference guidance from your database. Choose a crop to filter.</p><?php $ref=advisoriesFor($pdo,$crop?:'Other');if(!$ref)$ref=advisoriesFor($pdo,'Any');foreach($ref as $d):?><div class="ref-item"><h4><?=h($d['issue_name'])?></h4><p><strong><?=h($d['crop'])?> · <?=h($d['cause'])?></strong></p><p><?=h($d['first_steps'])?></p></div><?php endforeach;?><div class="notice">For serious damage or uncertain symptoms, consult a local agriculture expert. Do not choose/apply pesticides based only on this page; follow locally approved advice and product labels.</div></aside></div></main></div>
<script>const inp=document.getElementById('plant_photo'),prev=document.getElementById('photoPreview');inp.addEventListener('change',()=>{const f=inp.files&&inp.files[0];if(!f){prev.style.display='none';prev.removeAttribute('src');return;}prev.src=URL.createObjectURL(f);prev.style.display='block';});</script>
</body></html>
