#!/usr/bin/env bash

# ----------------------------------------------------------------------------------------------------------------------
# Prints the version name composer gives a git branch.
#
# Composer applies an entry of "extra.branch-alias" only when its key is exactly that name, and skips every other key
# without a word. A branch with a name that is not numeric is "dev-<name>", a numeric one is the normalized version
# with ".x-dev" in place of the open parts:
#
#   main  -> dev-main        2   -> 2.x-dev        2.x   -> 2.x-dev
#                            2.2 -> 2.2.x-dev      2.2.x -> 2.2.x-dev
#
# This mirrors "VersionParser::normalizeBranch()" of composer/semver and the way "VcsRepository" of composer names a
# branch from it. Refused with exit status 1 are the numeric names composer names differently as a package and as the
# root package ("v2" is "v2.x-dev" in a repository and "2.x-dev" as the root), and the numeric shapes no version
# branch uses ("2.2.3.4", "2.x.3"). "bin/set-version" writes the branch alias with it, "bin/cut-branch" checks the
# name of a new version branch with it, and the tests of "packages-dev" read the alias key back through it.
# ----------------------------------------------------------------------------------------------------------------------
set -euo pipefail

if [[ $# -ne 1 || -z "$1" ]]; then
    echo "Usage: Build/Scripts/composerBranchVersion.sh <branch>" >&2
    exit 1
fi
BRANCH="$1"

# Every name "normalizeBranch()" takes for a numeric one, case-insensitive there.
if [[ "${BRANCH}" =~ ^[vV]?[0-9]+(\.([0-9]+|[xX*])){0,3}$ ]]; then
    if [[ "${BRANCH}" =~ ^[0-9]+(\.[0-9]+){0,2}(\.[xX*])?$ ]]; then
        printf '%s.x-dev\n' "${BRANCH%.[xX*]}"
        exit 0
    fi
    echo "Branch '${BRANCH}' is numeric to composer, but not of the form N, N.M, N.M.P, N.x or N.M.x." >&2
    exit 1
fi

# Composer replaces "#" in a branch name, it is the separator of a commit reference in a constraint.
printf 'dev-%s\n' "${BRANCH//#/+}"
