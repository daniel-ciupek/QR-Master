#!/usr/bin/env bash
# QR-Master Git reminder hook (PreToolUse / Bash)
# Czyta JSON z stdin, sprawdza tool_input.command, dla git commit/push/tag wstrzykuje
# do contextu przypomnienie o regułach z CLAUDE.md sekcja 5.
# Reguły: zgoda usera + brak AI attribution + weryfikacja .gitignore + push osobno.

set -euo pipefail

INPUT=$(cat)

if command -v jq >/dev/null 2>&1; then
  CMD=$(echo "$INPUT" | jq -r '.tool_input.command // empty')
else
  # Fallback bez jq
  CMD=$(echo "$INPUT" | grep -oE '"command"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed 's/.*:[[:space:]]*"//;s/"$//')
fi

case "$CMD" in
  *"git commit"*)
    cat <<'EOF'
PRZYPOMNIENIE QR-Master (CLAUDE.md sekcja 5):
1. git status -u — sprawdź czy nie ma plików do .gitignore (logi, cache, sekrety, artefakty buildu)
2. git diff --cached --stat — pokaż userowi listę staged plików
3. ZAPYTAJ usera o zgodę na commit z proponowanym message — czekaj na jawne "tak" przed wykonaniem
4. Commit message BEZ stopki AI: ŻADNEGO "Co-Authored-By: Claude", "🤖 Generated with Claude Code", "Generated with Claude"
5. Po commicie OSOBNE pytanie o git push (commit ≠ push, dwie osobne decyzje)
EOF
    ;;
  *"git push"*)
    cat <<'EOF'
PRZYPOMNIENIE QR-Master (CLAUDE.md sekcja 5):
push to OSOBNA decyzja od commit. ZAPYTAJ usera o zgodę zanim wypchniesz.
Pokaż: gałąź docelową, listę commitów (git log origin/<branch>..HEAD --oneline).
EOF
    ;;
  *"git tag"*)
    cat <<'EOF'
PRZYPOMNIENIE QR-Master: tag release też wymaga zgody usera.
Pokaż propozycję nazwy tagu i message przed wykonaniem.
EOF
    ;;
  *"gh pr create"*)
    cat <<'EOF'
PRZYPOMNIENIE QR-Master (CLAUDE.md sekcja 5):
Body PR też BEZ AI attribution. ŻADNEGO "🤖 Generated with Claude Code" w opisie.
Pokaż userowi tytuł i body przed utworzeniem PR.
EOF
    ;;
esac

exit 0
