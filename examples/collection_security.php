<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dzentota\TemplateVariable\TemplateVariable;

echo "=== Collection Security Demonstration ===\n\n";

// Simulate ActiveRecord-style objects with user data
class User
{
    public function __construct(
        public string $name,
        public string $email,
        public string $bio
    ) {}
    
    public function __toString(): string
    {
        return $this->name;
    }
}

// Create users with potentially malicious content
$users = [
    new User(
        '<script>alert("XSS Admin")</script>',
        'admin@example.com',
        'System administrator with <b>elevated</b> privileges'
    ),
    new User(
        '" onload="alert(1)" class="danger',
        'user@example.com',
        'Regular user account'
    ),
    new User(
        'Safe User Name',
        'safe@example.com',
        'This user has safe content'
    )
];

echo "=== BEFORE: Traditional Approach (VULNERABLE) ===\n";
echo "foreach (\$users as \$user) {\n";
echo "    echo \"<div class=\\\"user\\\">{\$user->name}</div>\";\n";
echo "}\n\n";

echo "Vulnerable output:\n";
foreach ($users as $user) {
    echo "<div class=\"user\">{$user->name}</div>\n";
}
echo "\n☠️  XSS vulnerabilities present! Scripts would execute in browser.\n\n";

echo "=== AFTER: TemplateVariable Collection (SECURE) ===\n";
echo "\$secureUsers = TemplateVariable::create(\$users);\n";
echo "foreach (\$secureUsers as \$user) {\n";
echo "    echo \"<div class=\\\"user\\\">{\$user}</div>\";\n";
echo "}\n\n";

echo "Secure output:\n";
$secureUsers = TemplateVariable::create($users);
foreach ($secureUsers as $user) {
    echo "<div class=\"user\">{$user}</div>\n";
}
echo "\n✅ All content automatically escaped! No XSS possible.\n\n";

echo "=== Array Access Security ===\n";
echo "Direct array access is also secured:\n";
echo "First user: " . $secureUsers[0] . "\n";
echo "Second user: " . $secureUsers[1] . "\n";
echo "Third user: " . $secureUsers[2] . "\n\n";

echo "=== Nested Collections ===\n";
$departments = [
    'Engineering' => [
        new User('<script>alert("Lead Engineer")</script>', 'lead@eng.com', 'Technical lead'),
        new User('Junior Developer', 'junior@eng.com', 'Entry level developer')
    ],
    'Sales' => [
        new User('" onclick="alert(\'Sales Director\')"', 'director@sales.com', 'Sales director'),
        new User('Sales Rep', 'rep@sales.com', 'Sales representative')
    ]
];

$secureDepartments = TemplateVariable::create($departments);

echo "Nested collections are automatically secured:\n";
foreach ($secureDepartments as $deptName => $employees) {
    // $deptName is the key (string), $employees is TemplateVariableCollection
    echo "<h3>Department: {$deptName}</h3>\n";
    foreach ($employees as $employee) {
        // Each $employee is automatically wrapped as TemplateVariable
        echo "  - Employee: {$employee}\n";
    }
}
echo "\n";

echo "=== Context-Aware Collection Escaping ===\n";
$jsData = ['<script>alert(1)</script>', '" + alert(2) + "', 'safe data'];
$jsCollection = TemplateVariable::create($jsData, \Dzentota\TemplateVariable\Context\EscapeContext::JAVASCRIPT);

echo "JavaScript context collection:\n";
foreach ($jsCollection as $index => $item) {
    echo "data[{$index}] = '{$item}';\n";
}
echo "\n";

echo "=== Performance and Memory ===\n";
echo "Collection count: " . count($secureUsers) . "\n";
echo "Collection type: " . get_class($secureUsers) . "\n";
echo "Original data preserved: " . (($secureUsers->getRaw() === $users) ? 'Yes' : 'No') . "\n";
echo "Items wrapped on-demand: Yes (lazy wrapping for performance)\n\n";

echo "=== AppSec Manifesto Compliance ===\n";
echo "✅ Rule #1: Context-Specific Escaping - Applied at template output\n";
echo "✅ Rule #3: Preserving Data Validity - Original data structure maintained\n";
echo "✅ Secure by Default - No manual escaping required\n";
echo "✅ Fail-Safe Design - Collections cannot be modified\n\n";

echo "=== Integration with Existing Code ===\n";
echo "// Works with any Traversable object\n";
$iterator = new ArrayIterator($users);
$secureIterator = TemplateVariable::create($iterator);
echo "Traversable support: " . (($secureIterator instanceof \Dzentota\TemplateVariable\TemplateVariableCollection) ? 'Yes' : 'No') . "\n";

echo "\n=== Collection Security Demo Complete ===\n"; 