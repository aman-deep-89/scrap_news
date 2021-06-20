<?php
function testSetTimestampCount()
{
$id = '99';
$ip = '127.0.0.1';

$mysqli = (new db_connection())->connection();


$stmt = $this->createMock(PDOStatement::class);
$stmt->expects($this->once())
->method('execute');

$stmt->expects($this->exactly(2))
->method('bindValue')
->withConsecutive(
[':id', $id, PDO::PARAM_INT],
[':ip', $ip, PDO::PARAM_STR]
);

$pdo = $this->createMock('PDO');
$pdo->expects($this->once())
->method('prepare')
->with("INSERT INTO `ip` (`id`, `address`)VALUES (:id,:ip)")
->willReturn($stmt);

$validData = [
'id' => $id,
'ip' => $ip,
'mysqli' => $pdo
];

$ipStmt = new ip_request();
$ipStmt->setTimestampCount($validData,$mysqli);
}
function setTimestampCount($valueData,$mysqli)
{
$query = $mysqli->prepare ("INSERT INTO `ip` (`id`, `address`)VALUES (:id,:ip)");
$query ->bindValue(':id', $valueData['id'], PDO::PARAM_INT);
$query ->bindValue(':ip', $valueData['ip'], PDO::PARAM_STR);
$query->execute();
}

?>