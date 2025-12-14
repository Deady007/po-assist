param([Parameter(ValueFromRemainingArguments=$true)] [string[]] $SailArgs)

# Resolve current directory to Windows path
$pwdWin = (Resolve-Path .).Path

# Convert Windows path to WSL absolute path
$wslPath = wsl wslpath -a "$pwdWin" 2>$null
if ($LASTEXITCODE -ne 0 -or -not $wslPath) {
    Write-Error "WSL not available. Open a WSL shell and run './vendor/bin/sail <args>' instead."
    exit 1
}
$wslPath = $wslPath.Trim()

# Build argument string (escape single quotes)
$escapedArgs = ($SailArgs | ForEach-Object { ($_ -replace "'","'""'" ) }) -join ' '

wsl bash -lc "cd '$wslPath' && ./vendor/bin/sail $escapedArgs"