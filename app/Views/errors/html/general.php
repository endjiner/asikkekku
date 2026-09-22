<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title><?= esc($heading) ?></title>
<style>
::selection { background-color: #E13300; color: white; }
::-moz-selection { background-color: #E13300; color: white; }
body { background-color: #fff; margin: 40px; font: 13px/20px normal Helvetica, Arial, sans-serif; color: #4F5155; }
a { color: #003399; background-color: transparent; font-weight: normal; }
h1 { color: #444; background-color: transparent; border-bottom: 1px solid #D0D0D0; font-size: 19px; font-weight: bold; margin: 0 0 14px 0; padding: 14px 15px 10px 15px; }
p { margin: 0 0 10px 0; padding: 0 15px; }
</style>
</head>
<body>
<h1><?= esc($heading) ?></h1>
<p><?= nl2br(esc($message)) ?></p>
</body>
</html>
