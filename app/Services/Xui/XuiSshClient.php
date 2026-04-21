<?php

namespace App\Services\Xui;

use phpseclib3\Net\SSH2;
use Throwable;

final class XuiSshClient
{
    private SSH2 $ssh;
    private string $dbPath;

    public function __construct(string $host, int $port, string $username, string $privateKeyPath)
    {
        $this->ssh = new SSH2($host, $port);
        $key = \phpseclib3\Crypt\PublicKeyLoader::load(file_get_contents($privateKeyPath));
        
        if (!$this->ssh->login($username, $key)) {
            throw new XuiPanelException("SSH login failed to {$host}");
        }
        
        $this->dbPath = '/etc/x-ui/x-ui.db';
    }

    public function addInboundClient(int $inboundId, array $clientDef): void
    {
        $email = $clientDef['email'] ?? '';
        $clientJson = json_encode($clientDef, JSON_UNESCAPED_SLASHES);
        
        $script = <<<BASH
python3 << 'PYTHON_EOF'
import sqlite3
import json

db = sqlite3.connect('{$this->dbPath}')
cursor = db.cursor()

cursor.execute("SELECT settings FROM inbounds WHERE id=?", ({$inboundId},))
row = cursor.fetchone()
if not row:
    print("ERROR: Inbound {$inboundId} not found")
    exit(1)

settings = json.loads(row[0])
clients = settings.get('clients', [])

# Check if client exists
for c in clients:
    if c.get('email') == '{$email}':
        print("ERROR: Client {$email} already exists")
        exit(1)

# Add new client
new_client = json.loads('''{$clientJson}''')
clients.append(new_client)
settings['clients'] = clients

# Update DB
cursor.execute("UPDATE inbounds SET settings=? WHERE id=?", (json.dumps(settings), {$inboundId}))
db.commit()
db.close()

print("OK")
PYTHON_EOF
BASH;

        $output = $this->ssh->exec($script);
        
        if (str_contains($output, 'ERROR:')) {
            throw new XuiPanelException("SSH addClient failed: {$output}");
        }
        
        if (!str_contains($output, 'OK')) {
            throw new XuiPanelException("SSH addClient unexpected output: {$output}");
        }
    }

    public function restartXray(): void
    {
        $output = $this->ssh->exec('x-ui restart 2>&1');
        
        if (!str_contains($output, 'successfully') && !str_contains($output, 'Successfully')) {
            throw new XuiPanelException("Xray restart failed: {$output}");
        }
    }

    public function deleteInboundClientByEmail(int $inboundId, string $email): void
    {
        $script = <<<BASH
python3 << 'PYTHON_EOF'
import sqlite3
import json

db = sqlite3.connect('{$this->dbPath}')
cursor = db.cursor()

cursor.execute("SELECT settings FROM inbounds WHERE id=?", ({$inboundId},))
row = cursor.fetchone()
if not row:
    print("ERROR: Inbound not found")
    exit(1)

settings = json.loads(row[0])
clients = settings.get('clients', [])

# Filter out client
new_clients = [c for c in clients if c.get('email') != '{$email}']

if len(new_clients) == len(clients):
    print("ERROR: Client {$email} not found")
    exit(1)

settings['clients'] = new_clients

cursor.execute("UPDATE inbounds SET settings=? WHERE id=?", (json.dumps(settings), {$inboundId}))
db.commit()
db.close()

print("OK")
PYTHON_EOF
BASH;

        $output = $this->ssh->exec($script);
        
        if (str_contains($output, 'ERROR:')) {
            throw new XuiPanelException($output);
        }
    }
}
