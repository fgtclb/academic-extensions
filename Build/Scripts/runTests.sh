#!/usr/bin/env bash

# ----------------------------------------------------------------------------------------------------------------------
# fgtclb/academic-extensions test runner based on docker/podman.
# Adopted from TYPO3 Core Development and causal extension based additions.
# ----------------------------------------------------------------------------------------------------------------------
if [ "${CI}" != "true" ]; then
    trap 'echo "runTests.sh SIGINT signal emitted";cleanUp;exit 2' SIGINT
fi

printSummary() {
    cleanUp

    # Print summary
    echo "" >&2
    echo "###########################################################################" >&2
    echo "Result of ${TEST_SUITE}" >&2
    echo "Container runtime: ${CONTAINER_BIN}" >&2
    echo "Container suffix: ${SUFFIX}"
    if [[ ${IS_CORE_CI} -eq 1 ]]; then
        echo "Environment: CI" >&2
    else
        echo "Environment: local" >&2
    fi
    echo "PHP: ${PHP_VERSION}" >&2
    echo "TYPO3: ${CORE_VERSION}" >&2
    if [[ ${TEST_SUITE} =~ ^(functional|functionalDeprecated|acceptance|acceptanceInstall)$ ]]; then
        case "${DBMS}" in
            mariadb|mysql|postgres)
                echo "DBMS: ${DBMS}  version ${DBMS_VERSION}  driver ${DATABASE_DRIVER}" >&2
                ;;
            sqlite)
                echo "DBMS: ${DBMS}" >&2
                ;;
        esac
    fi
    if [[ ${SUITE_EXIT_CODE} -eq 0 ]]; then
        echo "SUCCESS" >&2
    else
        echo "FAILURE" >&2
    fi
    echo "###########################################################################" >&2
    echo "" >&2

    # Exit with code of test suite - This script return non-zero if the executed test failed.
    exit $SUITE_EXIT_CODE
}

# Make sure an image is available locally, retrying a failed pull.
#
# `run` pulls implicitly on a cache miss, and a single failed pull ends the whole
# job with exit 125 before a test has run - twice within three hours on GitHub
# Actions, both times on a docker.io image (ACE-342). Anonymous Docker Hub pulls
# are rate limited per source IP and hosted runners share address space, which
# also explains why no ghcr.io pull has failed; only the docker.io images are
# guarded here.
#
# The local check comes first so a warm cache still works offline and a pinned
# image is not re-fetched on every local run.
ensureImage() {
    local IMAGE=${1}
    local MAX_ATTEMPTS=3
    local ATTEMPT=1
    if ${CONTAINER_BIN} image inspect "${IMAGE}" >/dev/null 2>&1; then
        return 0
    fi
    while [ ${ATTEMPT} -le ${MAX_ATTEMPTS} ]; do
        if ${CONTAINER_BIN} pull "${IMAGE}"; then
            return 0
        fi
        if [ ${ATTEMPT} -lt ${MAX_ATTEMPTS} ]; then
            echo "Pulling ${IMAGE} failed (attempt ${ATTEMPT} of ${MAX_ATTEMPTS}), retrying in $((ATTEMPT * 5))s." >&2
            sleep $((ATTEMPT * 5))
        fi
        ATTEMPT=$((ATTEMPT + 1))
    done
    echo "Could not pull ${IMAGE} after ${MAX_ATTEMPTS} attempts. Aborting." >&2
    return 1
}

waitFor() {
    local HOST=${1}
    local PORT=${2}
    # 60 rather than 10 seconds: mysql:8.0 needs 12-13s under docker to initialise a fresh
    # data directory, about twice as long as under podman, so an 11 second budget aborted
    # the functional mysql suites at random.
    local TESTCOMMAND="
        COUNT=0;
        while ! nc -z ${HOST} ${PORT}; do
            if [ \"\${COUNT}\" -gt 60 ]; then
              echo \"Can not connect to ${HOST} port ${PORT}. Aborting.\";
              exit 1;
            fi;
            sleep 1;
            COUNT=\$((COUNT + 1));
        done;
    "
    ensureImage "${IMAGE_ALPINE}" || { cleanUp; exit 1; }
    ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name wait-for-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_ALPINE} /bin/sh -c "${TESTCOMMAND}"
    if [[ $? -gt 0 ]]; then
        # Not "kill -SIGINT -$$": the SIGINT trap is only installed when CI is not "true",
        # so in CI the signal was a no-op, the run continued and the test suite connected
        # to a database that was not listening.
        cleanUp
        exit 1
    fi
}

cleanUp() {
    ATTACHED_CONTAINERS=$(${CONTAINER_BIN} ps --filter network=${NETWORK} --format='{{.Names}}')
    for ATTACHED_CONTAINER in ${ATTACHED_CONTAINERS}; do
        ${CONTAINER_BIN} rm -f ${ATTACHED_CONTAINER} >/dev/null
    done
    ${CONTAINER_BIN} network rm -f ${NETWORK} >/dev/null
}

handleDbmsOptions() {
    # -a, -d, -i depend on each other. Validate input combinations and set defaults.
    case ${DBMS} in
        mariadb)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="10.4"
            if ! [[ ${DBMS_VERSION} =~ ^(10.4|10.5|10.6|10.7|10.8|10.9|10.10|10.11|11.0|11.1|11.2|11.2|11.3|11.4)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        mysql)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="8.0"
            if ! [[ ${DBMS_VERSION} =~ ^(8.0|8.1|8.2|8.3|8.4)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        postgres)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="10"
            if ! [[ ${DBMS_VERSION} =~ ^(10|11|12|13|14|15|16)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        sqlite)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            if [ -n "${DBMS_VERSION}" ]; then
                echo "Invalid combination -d ${DBMS} -i ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        *)
            echo "Invalid option -d ${DBMS}" >&2
            echo >&2
            echo "Use \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
            exit 1
            ;;
    esac
}

cleanCacheFiles() {
    echo -n "Clean caches ... "
    rm -rf \
        .cache \
        .php-cs-fixer.cache
    echo "done"
}

cleanTestFiles() {
    # test related
    echo -n "Clean test related files ... "
    rm -rf \
        .Build/Web/typo3temp/var/tests/
    echo "done"
}

cleanRenderedDocumentationFiles() {
    echo -n "Clean rendered documentation files ... "
    rm -rf \
        Documentation-GENERATED-temp
    echo "done"
}

cleanJsFiles() {
    # Intermediates of the frontend asset build only. The compiled artifacts
    # below the packages are committed files and are never removed here - use
    # "checkJsBuildClean", which deletes and rebuilds them on purpose.
    echo -n "Clean frontend build related files ... "
    rm -rf \
        Build/node_modules
    echo "done"
}

executeRstRendering() {
    local extensionFolderName="$1"
    local extensionFolder="packages/fgtclb/${extensionFolderName}"
    if [[ ! -d "${extensionFolder}/Documentation" ]]; then
        return 1
    fi
    echo "Processing RST directory: ${extensionFolder}/Documentation"
    ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name check-rst-rendering-${extensionFolderName}-${SUFFIX}  -w /project -v "${ROOT_DIR}/${extensionFolder}:/project" ${IMAGE_RSTRENDERING} --fail-on-log --fail-on-error --no-progress --config=Documentation Documentation
    local exitCode=$?
    echo "Render result for ${extensionFolder}: ${exitCode}"
    rm -rf "documentation-rendered/${extensionFolderName}/Documentation-GENERATED-temp" && \
      mkdir -p "documentation-rendered/${extensionFolderName}/Documentation-GENERATED-temp" && \
      cp -Rf "${extensionFolder}/Documentation-GENERATED-temp" "documentation-rendered/${extensionFolderName}/" || exitCode=1
    return ${exitCode}
}

openDocumentation() {
    local extensionFolderName="$1"
    local documentationFolder="documentation-rendered/${extensionFolderName}/Documentation-GENERATED-temp"
    if [[ ! -d "${documentationFolder}" ]]; then
        echo "ERROR: No rendered documentation found for ${extensionFolderName}"
        echo ""
        echo "       Run ./Build/Scripts/runtests.sh -s checkRstRenderingAll"
        echo "        or  ./Build/Scripts/runtests.sh -s checkRstRenderingSingle <extension-folder-key>"
        echo ""
        echo "       first to have a rendered documentation in place."
        echo ""
        return 1
    fi
    # @todo Make this OS aware, currently only linux supported.
    xdg-open "${documentationFolder}/Index.html"
}

loadHelp() {
    # Load help text into $HELP
    read -r -d '' HELP <<EOF
Test runner of fgtclb/academic-extensions. Executes the unit, functional, linting,
static analysis, documentation and frontend suites in a container based environment,
so nothing but docker or podman has to be installed on the host. Handles execution of
single test files, sending xdebug information to a local IDE and more.

Usage: $0 [options] [file] [-- <arguments>]

A trailing file or directory restricts the phpunit based suites to it. Everything
after a "--" separator is appended to the command the suite runs, which is how
options are handed to phpunit, composer and npm:

    ./Build/Scripts/runTests.sh -s unit -- --filter SomeTest

Options:
    -s <...>
        Specifies which test suite to run
            - buildJs: compile the frontend sources of every extension into its Resources/Public/
            - cgl: test and fix all php files, "-n" to only check
            - cglHeader: test and fix the file header of all php files, "-n" to only check
            - checkJsBuildClean: check the committed frontend artifacts match their sources
            - checkRstRenderingAll: Test all extension .rst files for rendering errors
            - checkRstRenderingSingle: Test specified system extension .rst files for rendering errors
            - cleanJs: clean up frontend build related files and folders (Build/node_modules)
            - composer: "composer" with all remaining arguments dispatched.
            - composerUpdate: "composer update", handy if host has no PHP
            - functional: PHP functional tests
            - lintMarkdown: check docs/ and the README/CONTRIBUTING files, fixes by default, "-n" to only check
            - lintPhp: PHP linting
            - lintTypescript: eslint over the TypeScript sources, fixes by default, "-n" to only check
            - npm: "npm" with all remaining arguments dispatched, run in Build/
            - openDocumentation: Open a rendered extension documentation in the browser (only linux for now)
            - phpstan: phpstan tests
            - phpstanGenerateBaseline: regenerate phpstan baseline, handy after phpstan updates
            - typecheckJs: "tsc --noEmit" over the TypeScript sources, which the build does not do
            - unit: PHP unit tests
            - unitRandom: PHP unit tests in random order, "-o <number>" to use a specific seed
            - update: update the typo3/core-testing-* images, same as "-u"
            - help: show this help, the default when "-s" is not given

        The node suites - buildJs, checkJsBuildClean, lintMarkdown, lintTypescript, npm
        and typecheckJs - run in a node container, and cleanJs runs on the host. All seven
        are core version independent: they look at the sources and the committed artifacts
        and never at the installed core, so "-t" does not change what they do. They also
        need no composerUpdate, which makes them the only suites that are safe to run while
        the other core version's dependency set is installed. lintMarkdown needs no node
        dependency either, so it runs without an "npm ci" first.

    -b <docker|podman>
        Container environment:
            - docker
            - podman

        If not specified, podman will be used if available. Otherwise, docker is used.

    -a <mysqli|pdo_mysql>
        Only with -s functional
        Specifies to use another driver, following combinations are available:
            - mysql
                - mysqli (default)
                - pdo_mysql
            - mariadb
                - mysqli (default)
                - pdo_mysql

    -d <sqlite|mariadb|mysql|postgres>
        Only with -s functional
        Specifies on which DBMS tests are performed
            - sqlite: (default): use sqlite
            - mariadb: use mariadb
            - mysql: use MySQL
            - postgres: use postgres

    -i version
        Specify a specific database version
        With "-d mariadb":
            - 10.4   short-term, maintained until 2024-06-18 (default)
            - 10.5   short-term, maintained until 2025-06-24
            - 10.6   long-term, maintained until 2026-06
            - 10.7   short-term, no longer maintained
            - 10.8   short-term, maintained until 2023-05
            - 10.9   short-term, maintained until 2023-08
            - 10.10  short-term, maintained until 2023-11
            - 10.11  long-term, maintained until 2028-02
            - 11.0   development series
            - 11.1   short-term development series
            - 11.2   short-term development series, maintained until 2024-11
            - 11.3   short-term development series, rolling release
            - 11.4   long-term, maintained until 2029-05
        With "-d mysql":
            - 8.0   maintained until 2026-04 (default) LTS
            - 8.1   unmaintained since 2023-10
            - 8.2   unmaintained since 2024-01
            - 8.3   maintained until 2024-04
            - 8.4   maintained until 2032-04 LTS
        With "-d postgres":
            - 10    unmaintained since 2022-11-10 (default)
            - 11    maintained until 2023-11-09
            - 12    maintained until 2024-11-14
            - 13    maintained until 2025-11-13
            - 14    maintained until 2026-11-12
            - 15    maintained until 2027-11-11
            - 16    maintained until 2028-11-09

    -t <13|14>
        Only with -s composerUpdate|functional|phpstan|phpstanGenerateBaseline|unit|unitRandom
        Specifies the TYPO3 core version to be used
            - 13: (default) use TYPO3 v13
            - 14: use TYPO3 v14

        It selects configuration only and does not install anything: composerUpdate
        resolves against it, phpstan picks Build/phpstan/Core<version>/phpstan.neon,
        and the test suites exclude the group "not-core-<version>". Running a suite
        for one version while the other one's dependencies are installed fails in
        confusing ways, so run composerUpdate for the version to be tested first.

    -p <8.2|8.3|8.4|8.5>
        Specifies the PHP minor version to be used
            - 8.2: (default) use PHP 8.2
            - 8.3: use PHP 8.3
            - 8.4: use PHP 8.4
            - 8.5: use PHP 8.5

    -x
        Only with -s functional|unit|unitRandom
        Send information to host instance for test or system under test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -o <number>
        Only with -s unitRandom
        Set the random order seed, so a failing random order run can be repeated:
        "--random-order-seed=<number>" is passed on to phpunit.

    -n
        Only with -s cgl|cglHeader|lintMarkdown|lintTypescript
        Activate dry-run, which does not change files and only reports the broken ones.

    -u
        Update existing typo3/core-testing-*:latest container images and remove dangling local volumes.
        New images are published once in a while and only the latest ones are supported by core testing.
        Use this if weird test errors occur. Also removes obsolete image versions of typo3/core-testing-*.

    -h
        Show this help.

Examples:
    # Install the dependencies of a core version, which every PHP suite needs first
    ./Build/Scripts/runTests.sh -s composerUpdate -t 14 -p 8.3

    # Run all unit tests using PHP 8.2
    ./Build/Scripts/runTests.sh -s unit
    ./Build/Scripts/runTests.sh -s unit -p 8.2

    # Run all unit tests against TYPO3 v14 using PHP 8.3
    ./Build/Scripts/runTests.sh -s unit -t 14 -p 8.3

    # Repeat a failing random order unit run with the seed it reported
    ./Build/Scripts/runTests.sh -s unitRandom -o 1234

    # Run all unit tests and enable xdebug (have a PhpStorm listening on port 9003!)
    ./Build/Scripts/runTests.sh -x

    # Run the unit tests of a single test file with xdebug on PHP 8.3
    ./Build/Scripts/runTests.sh -x -p 8.3 -s unit packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/ProfileInformationTest.php

    # Run functional tests on postgres with xdebug, php 8.3 and execute a restricted set of tests
    ./Build/Scripts/runTests.sh -x -p 8.3 -s functional -d postgres packages/fgtclb/academic-persons/Tests/Functional/Domain

    # Run functional tests on postgres 16
    ./Build/Scripts/runTests.sh -s functional -d postgres -i 16

    # Compile the frontend assets after a change below an extension's Resources/Private/
    ./Build/Scripts/runTests.sh -s buildJs

    # Prove the committed artifacts still match their sources, as CI does
    ./Build/Scripts/runTests.sh -s checkJsBuildClean

    # Add or update a node dependency, arguments after "--"
    ./Build/Scripts/runTests.sh -s npm -- install --save-dev sass@latest
EOF
}

# Test if docker exists, else exit out with error
if ! type "docker" >/dev/null 2>&1 && ! type "podman" >/dev/null 2>&1; then
    echo "This script relies on docker or podman. Please install" >&2
    exit 1
fi

# Option defaults
TEST_SUITE="help"
CORE_VERSION="13"
DBMS="sqlite"
PHP_VERSION="8.2"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
PHPUNIT_RANDOM=""
CGLCHECK_DRY_RUN=0
DATABASE_DRIVER=""
DBMS_VERSION=""
CONTAINER_BIN=""
CONTAINER_HOST="host.docker.internal"

# Option parsing updates above default vars
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=()
# Simple option parsing based on getopts (! not getopt)
while getopts "a:b:s:d:i:p:t:xy:o:nhu" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        b)
            if ! [[ ${OPTARG} =~ ^(docker|podman)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            CONTAINER_BIN=${OPTARG}
            ;;
        a)
            DATABASE_DRIVER=${OPTARG}
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        i)
            DBMS_VERSION=${OPTARG}
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8.2|8.3|8.4|8.5)$ ]]; then
                INVALID_OPTIONS+=("p ${OPTARG}")
            fi
            ;;
        t)
            CORE_VERSION=${OPTARG}
            if ! [[ ${CORE_VERSION} =~ ^(13|14)$ ]]; then
                INVALID_OPTIONS+=("t ${OPTARG}")
            fi
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        o)
            PHPUNIT_RANDOM="--random-order-seed=${OPTARG}"
            ;;
        n)
            CGLCHECK_DRY_RUN=1
            ;;
        h)
            loadHelp
            echo "${HELP}"
            exit 0
            ;;
        u)
            TEST_SUITE=update
            ;;
        \?)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-"${I} >&2
    done
    echo >&2
    echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"
    exit 1
fi

handleDbmsOptions

COMPOSER_ROOT_VERSION="3.0.0-dev"
CONTAINER_INTERACTIVE="-it --init"
HOST_UID=$(id -u)
HOST_GID=$(id -g)
# Additional container parameters, provided by the environment. Empty unless the caller
# exports it, which is how the portfolio harnesses inject CI specific flags.
CI_PARAMS="${CI_PARAMS:-}"
USERSET=""
if [ $(uname) != "Darwin" ]; then
    USERSET="--user $HOST_UID"
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called, then go up two dirs.
THIS_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
cd "$THIS_SCRIPT_DIR" || exit 1
cd ../../ || exit 1
ROOT_DIR="${PWD}"

# Create .cache dir: composer need this.
mkdir -p .cache/composer
mkdir -p .Build/Web/typo3temp/var/tests


IS_CORE_CI=0
# ENV var "CI" is set by gitlab-ci. We use it here to distinct 'local' and 'CI' environment.
if [ "${CI}" == "true" ]; then
    IS_CORE_CI=1
    IMAGE_PREFIX=""
    CONTAINER_INTERACTIVE=""
elif [ ! -t 0 ] || [ ! -t 1 ]; then
    # If stdin or stdout is not a TTY (e.g. a script runner, pipe, or non-interactive shell),
    # drop the interactive "-it" flags automatically to avoid podman warning "The input device
    # is not a TTY." and docker failure, and to keep redirected output free of TTY control characters.
    # Keep "--init" so the PID 1 init process still forwards signals (e.g. ctrl-c) to the test process.
    #
    # It also stops a scripted run from hanging forever: with a pseudo TTY every tool inside the
    # container believes it may ask a question, and composer does - it asks whether a plugin missing
    # from "allow-plugins" is trusted, and then waits for an answer nobody is there to give (ACE-383).
    CONTAINER_INTERACTIVE="--init"
fi

# determine default container binary to use: 1. podman 2. docker
if [[ -z "${CONTAINER_BIN}" ]]; then
    if type "podman" >/dev/null 2>&1; then
        CONTAINER_BIN="podman"
    elif type "docker" >/dev/null 2>&1; then
        CONTAINER_BIN="docker"
    fi
fi

IMAGE_PHP="ghcr.io/typo3/core-testing-$(echo "php${PHP_VERSION}" | sed -e 's/\.//'):latest"
IMAGE_ALPINE="docker.io/alpine:3.8"
IMAGE_DOCS="ghcr.io/typo3-documentation/render-guides:latest"
IMAGE_SELENIUM="docker.io/selenium/standalone-chrome:4.0.0-20211102"
IMAGE_MARIADB="docker.io/mariadb:${DBMS_VERSION}"
IMAGE_MYSQL="docker.io/mysql:${DBMS_VERSION}"
IMAGE_POSTGRES="docker.io/postgres:${DBMS_VERSION}-alpine"
IMAGE_RSTRENDERING="ghcr.io/typo3-documentation/render-guides:latest"
# The image TYPO3 core itself uses for its JavaScript suites. It carries node 24
# and npm 11, which match the "engines" range of Build/package.json, and it ships
# git, which "checkJsBuildClean" needs. Pinned rather than ":latest", the way core
# pins it - a node major changing under a committed build artifact is exactly the
# kind of surprise that gate exists to catch, not to produce.
IMAGE_NODEJS="ghcr.io/typo3/core-testing-nodejs24:1.1"

# Detect arm64 and use a seleniarm image.
# In a perfect world selenium would have a arm64 integrated, but that is not on the horizon.
# So for the time being we have to use seleniarm image.
ARCH=$(uname -m)
if [ ${ARCH} = "arm64" ]; then
    IMAGE_SELENIUM="docker.io/seleniarm/standalone-chromium:4.1.2-20220227"
fi
# echo "Architecture" ${ARCH} "requires" ${IMAGE_SELENIUM} "to run acceptance tests."

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))

SUFFIX=$(echo $RANDOM)
NETWORK="academic-extensions-${SUFFIX}"
${CONTAINER_BIN} network create ${NETWORK} >/dev/null

# Suffix every bind mount needs, kept next to the mounts that use it. Empty except for
# podman on Linux, which relabels the mount for SELinux - see the assignments below.
CONTAINER_MOUNT_SUFFIX=""

if [ "${CONTAINER_BIN}" == "docker" ]; then
    # docker needs the add-host for xdebug remote debugging. podman has host.container.internal built in
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm --network ${NETWORK} --add-host ${CONTAINER_HOST}:host-gateway ${USERSET} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
    CONTAINER_SIMPLE_PARAMS="${CONTAINER_INTERACTIVE} --rm --network ${NETWORK} --add-host ${CONTAINER_HOST}:host-gateway ${USERSET} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
    # docker creates the tmpfs owned by "root:root", while "${USERSET}" above passes a uid
    # but no group, so the container runs as "uid=${HOST_UID} gid=0" and does not own the
    # mount it has to write the test databases into. Whether that still works then depends
    # on the mode docker happens to give the tmpfs: 1777 by default on docker 29, but 0755
    # where it follows the umask, and then no test database can be created and every test
    # fails with "unable to open database file".
    #
    # "uid"/"gid" remove that dependency at the source - the mount is owned by the user the
    # container runs as. "mode=1777" is the workaround the docker adoption introduced
    # instead, and is kept next to them: it is what has been proven on a GitHub hosted
    # runner, and it costs nothing to leave in place.
    TMPFS_MOUNT_OPTIONS="rw,noexec,nosuid,uid=${HOST_UID},gid=${HOST_GID},mode=1777"
else
    # podman
    CONTAINER_HOST="host.containers.internal"
    # Rootless podman maps the container root to the host user, so the tmpfs is writable
    # without an explicit owner. "mode=1777" is kept for the rootful case.
    TMPFS_MOUNT_OPTIONS="rw,noexec,nosuid,mode=1777"
    if [ $( uname ) = "Linux" ]; then
        CONTAINER_MOUNT_SUFFIX=":Z"
        CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm --network ${NETWORK} -v ${ROOT_DIR}:${ROOT_DIR}:Z -w ${ROOT_DIR}"
        CONTAINER_SIMPLE_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm -v ${ROOT_DIR}:${ROOT_DIR}:Z -w ${ROOT_DIR}"
    else
        CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm --network ${NETWORK} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
        CONTAINER_SIMPLE_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
    fi
fi

if [ ${PHP_XDEBUG_ON} -eq 0 ]; then
    XDEBUG_MODE="-e XDEBUG_MODE=off"
    XDEBUG_CONFIG=" "
else
    XDEBUG_MODE="-e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=foo"
    XDEBUG_CONFIG="client_port=${PHP_XDEBUG_PORT} client_host=${CONTAINER_HOST}"
fi

# Suite execution
case ${TEST_SUITE} in
    buildJs)
        COMMAND="cd Build && npm ci --no-audit --no-fund && npm run build"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name build-js-${SUFFIX} -e npm_config_cache=${ROOT_DIR}/.cache/npm ${IMAGE_NODEJS} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    checkJsBuildClean)
        # The gate that makes committed build artifacts trustworthy. The compiled
        # files below "Resources/Public/" are tracked because neither composer nor
        # a TER upload runs a node build, and an artifact that no longer matches
        # its source passes every review, ships to every installation and is only
        # noticed when someone wonders why a fix had no effect.
        #
        # Only the files the build produces are deleted, not the output folders:
        # those also hold vendored files that have no source - a minified library
        # and its images - and deleting them would report a permanently dirty tree.
        # "--list-outputs" derives that set from the same discovery the build uses,
        # so a source file that stopped producing an output is caught as well, as
        # a deletion in "git status".
        #
        # "safe.directory" is passed as environment rather than written to a config
        # file: in CI the container runs against a checkout owned by another user,
        # and git refuses to operate in a repository owned by someone else.
        COMMAND="export GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0=safe.directory GIT_CONFIG_VALUE_0='*'; \
            cd Build && npm ci --no-audit --no-fund || exit 1; \
            cd ${ROOT_DIR} || exit 1; \
            (cd Build && npm run outputs --silent) | while read -r ARTIFACT; do rm -rf \"\${ARTIFACT}\"; done; \
            (cd Build && npm run build) || exit 1; \
            CHANGED=\$(git status --porcelain --untracked-files=all -- 'packages/*/*/Resources/Public/*' 'packages-dev/*/Resources/Public/*'); \
            if [ -n \"\${CHANGED}\" ]; then \
                echo ''; \
                echo 'The committed frontend artifacts do not match their sources:'; \
                echo \"\${CHANGED}\"; \
                echo ''; \
                git --no-pager diff -- 'packages/*/*/Resources/Public/*' 'packages-dev/*/Resources/Public/*'; \
                echo ''; \
                echo 'Run \"Build/Scripts/runTests.sh -s buildJs\" and commit the result.'; \
                exit 1; \
            fi; \
            echo 'The committed frontend artifacts match their sources.'"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name check-js-build-clean-${SUFFIX} -e npm_config_cache=${ROOT_DIR}/.cache/npm ${IMAGE_NODEJS} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    cleanJs)
        cleanJsFiles
        SUITE_EXIT_CODE=$?
        ;;
    cgl)
        # Active dry-run for cgl needs not "-n" but specific options
        CSFIXER_DRYRUN=""""
        if [ "${CGLCHECK_DRY_RUN}" -eq 1 ]; then
            CSFIXER_DRYRUN="--dry-run --diff"
        fi
        COMMAND="php -dxdebug.mode=off .Build/bin/php-cs-fixer fix -v ${CSFIXER_DRYRUN} --config=Build/php-cs-fixer/config.php"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name cgl-${SUFFIX} ${IMAGE_PHP} ${COMMAND}
        SUITE_EXIT_CODE=$?
        ;;
    cglHeader)
        # Active dry-run for cgl needs not "-n" but specific options
        CSFIXER_DRYRUN=""""
        if [ "${CGLCHECK_DRY_RUN}" -eq 1 ]; then
            CSFIXER_DRYRUN="--dry-run --diff"
        fi
        COMMAND="php -dxdebug.mode=off .Build/bin/php-cs-fixer fix -v ${CSFIXER_DRYRUN} --config=Build/php-cs-fixer/header-comment.php"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name cgl-header-${SUFFIX} ${IMAGE_PHP} ${COMMAND}
        SUITE_EXIT_CODE=$?
        ;;
    checkRstRenderingAll)
        SUITE_EXIT_CODE=0
        echo "Scanning packages/fgtclb/ for directories with Documentation..."
        for extensionFolder in packages/fgtclb//*/Documentation; do
            extensionFolderName="${extensionFolder%/Documentation}"
            extensionFolderName=$(basename "${extensionFolderName}")
            executeRstRendering "${extensionFolderName}"
            TMP_SUITE_EXIT_CODE=$?
            if [ ${TMP_SUITE_EXIT_CODE} -ne 0 ]; then
                SUITE_EXIT_CODE=${TMP_SUITE_EXIT_CODE}
            fi
        done
        ;;
    checkRstRenderingSingle)
        extensionKey="${1}"
        if [ -n "${extensionKey}" ]; then
            if [[ ! -d "packages/fgtclb/${extensionKey}" ]]; then
                echo "Error: Invalid extension key provided: \"${systemExtensionKey}\""
                SUITE_EXIT_CODE=1
            elif [[ ! -d "packages/fgtclb/${extensionKey}/Documentation" ]]; then
                echo "Error: Valid extension \"${extensionKey}\" does not contain a \"Documentation\" folder"
                SUITE_EXIT_CODE=1
            else
                executeRstRendering "${extensionKey}"
                SUITE_EXIT_CODE=$?
            fi
        else
            echo "Error: No system extension key provided as first argument"
            SUITE_EXIT_CODE=1
        fi
        ;;
    composer)
        COMMAND=(composer "$@")
        ${CONTAINER_BIN} run ${CONTAINER_SIMPLE_PARAMS} --name composer-command-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    composerUpdate)
        rm -rf .Build composer.lock composer.json.orig
        \cp -f composer.json composer.json.orig
        ${CONTAINER_BIN} run ${CONTAINER_SIMPLE_PARAMS} --name composer-command-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} composer require --dev --no-update "typo3/minimal":"^${CORE_VERSION}"
        SUITE_EXIT_CODE=$?
        if [[ "${SUITE_EXIT_CODE}" -eq 0 ]]; then
          ${CONTAINER_BIN} run ${CONTAINER_SIMPLE_PARAMS} --name composer-command-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} composer install
          SUITE_EXIT_CODE=$?
        fi
        [[ -f composer.json.orig ]] && \cp -f composer.json.orig composer.json
        ;;
    functional)
        PHPUNIT_CONFIG_FILE="Build/phpunit/FunctionalTests.xml"
        COMMAND=(.Build/bin/phpunit -c ${PHPUNIT_CONFIG_FILE} --exclude-group not-${DBMS} --exclude-group not-core-${CORE_VERSION} "$@")
        # Each functional test case gets its own TYPO3 instance below
        # "typo3temp/var/tests/functional-<identifier>". The testing framework derives that
        # identifier itself, as "substr(sha1(<test class>), 0, 7)", so it is the same in every
        # run of the same test class - see FunctionalTestCase::getInstanceIdentifier().
        #
        # Two runs in one checkout therefore work in the *same* instance directories, and
        # "removeOldInstanceIfExists()" of the one deletes the instance the other is currently
        # using. It surfaces as "No such file or directory", "no such table" and "UNIQUE
        # constraint failed" in tests that have nothing to do with each other, and it cost
        # roughly one run in three during the ACE-403 .. ACE-422 round.
        #
        # Give every run its own directory on the host and mount it where the testing framework
        # expects to find it, so concurrent runs cannot see each other's instances. A bind mount
        # rather than a tmpfs on purpose: 150 test classes at some 5 MB each would put the better
        # part of a gigabyte into RAM, and a failed run stays inspectable this way.
        FUNCTIONAL_INSTANCE_DIR="${ROOT_DIR}/.Build/Web/typo3temp/var/tests-${SUFFIX}"
        mkdir -p "${FUNCTIONAL_INSTANCE_DIR}"
        SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
        CONTAINER_COMMON_PARAMS="${CONTAINER_COMMON_PARAMS} -v ${FUNCTIONAL_INSTANCE_DIR}:${ROOT_DIR}/.Build/Web/typo3temp/var/tests${CONTAINER_MOUNT_SUFFIX}"
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                ensureImage "${IMAGE_MARIADB}"
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                ${CONTAINER_BIN} run --name mariadb-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid ${IMAGE_MARIADB} >/dev/null
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                waitFor mariadb-func-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mariadb-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                ensureImage "${IMAGE_MYSQL}"
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                ${CONTAINER_BIN} run --name mysql-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid ${IMAGE_MYSQL} >/dev/null
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                waitFor mysql-func-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mysql-func-${SUFFIX} -e typo3DatabasePassword=funcp "
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                ensureImage "${IMAGE_POSTGRES}"
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                ${CONTAINER_BIN} run --name postgres-func-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid ${IMAGE_POSTGRES} >/dev/null
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                waitFor postgres-func-${SUFFIX} 5432
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_pgsql -e typo3DatabaseName=bamboo -e typo3DatabaseUsername=funcu -e typo3DatabaseHost=postgres-func-${SUFFIX} -e typo3DatabasePassword=funcp "
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                # create sqlite tmpfs mount typo3temp/var/tests/functional-sqlite-dbs/ to avoid permission issues
                #
                # The directory is created inside this run's own instance directory, which is
                # mounted over "typo3temp/var/tests" above. It is therefore empty already and
                # is not shared with any other run, so nothing has to be removed first.
                mkdir -p "${FUNCTIONAL_INSTANCE_DIR}/functional-sqlite-dbs"
                SUITE_EXIT_CODE=$? && [[ "${SUITE_EXIT_CODE}" -ne 0 ]] && printSummary
                # "${TMPFS_MOUNT_OPTIONS}" carries the owner and mode the mount needs, which
                # differ per container binary - see where it is assigned. Without them the
                # test databases cannot be created and every test fails with "unable to open
                # database file".
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${ROOT_DIR}/.Build/Web/typo3temp/var/tests/functional-sqlite-dbs/:${TMPFS_MOUNT_OPTIONS} "
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
        esac
        # A green run has nothing left to look at, and the instances are some 5 MB each. A red
        # one is kept, because the instance of a failing test - its configuration, its
        # typo3temp, the files a test wrote - is usually where the answer is.
        if [[ ${SUITE_EXIT_CODE} -eq 0 ]]; then
            rm -rf "${FUNCTIONAL_INSTANCE_DIR}"
        else
            echo "Test instances of this run kept for inspection: ${FUNCTIONAL_INSTANCE_DIR}"
        fi
        ;;
    lintPhp)
        # The DDEV instances install their own vendor tree next to the sources.
        # It is git ignored and holds third party files that are not ours to
        # lint - class-alias-loader ships a template that is not valid PHP.
        #
        # ".agent/" is the git ignored working tree of AI coding agents (see
        # AGENTS.md). It holds drafts and partial snippets that are none of this
        # repository's business, and a snippet that does not parse would turn
        # this gate red for a file that is not part of the code base.
        #
        # "Build/node_modules" is the frontend build's dependency tree, and npm
        # packages do ship PHP - "flatted" carries a PHP port of itself.
        COMMAND="find . -name \\*.php ! -path "./.Build/\\*" ! -path "./.agent/\\*" ! -path "./Build/node_modules/\\*" ! -path "./core-1\\*/vendor/\\*" ! -path "./core-1\\*/public/\\*" ! -path "./core-1\\*/var/\\*" -print0 | xargs -0 -n1 -P4 php -dxdebug.mode=off -l >/dev/null"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name lint-php-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    lintMarkdown)
        # Mirrors "cgl": it fixes in place, and only checks when "-n" is given.
        #
        # No "npm ci" first, unlike its node siblings: Build/markdown.mjs uses
        # nothing but the node standard library, so installing the frontend
        # dependency tree would only make the gate slower.
        MARKDOWN_ARGUMENTS="--fix"
        if [ "${CGLCHECK_DRY_RUN}" -eq 1 ]; then
            MARKDOWN_ARGUMENTS=""
        fi
        COMMAND="node Build/markdown.mjs ${MARKDOWN_ARGUMENTS}"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name lint-markdown-${SUFFIX} ${IMAGE_NODEJS} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    lintTypescript)
        # Mirrors "cgl": it fixes in place, and only checks when "-n" is given.
        NPM_LINT_SCRIPT="lint:fix"
        if [ "${CGLCHECK_DRY_RUN}" -eq 1 ]; then
            NPM_LINT_SCRIPT="lint"
        fi
        COMMAND="cd Build && npm ci --no-audit --no-fund && npm run ${NPM_LINT_SCRIPT}"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name lint-typescript-${SUFFIX} -e npm_config_cache=${ROOT_DIR}/.cache/npm ${IMAGE_NODEJS} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    npm)
        # Escape hatch, mirroring the "composer" suite:
        #   ./Build/Scripts/runTests.sh -s npm -- install --save-dev sass@latest
        # The working directory is overridden to Build/, where package.json lives.
        COMMAND=(npm "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} -w ${ROOT_DIR}/Build --name npm-${SUFFIX} -e npm_config_cache=${ROOT_DIR}/.cache/npm ${IMAGE_NODEJS} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    typecheckJs)
        # Its own suite precisely because esbuild does not type check: without
        # this the build succeeds on TypeScript that does not compile.
        COMMAND="cd Build && npm ci --no-audit --no-fund && npm run typecheck"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name typecheck-js-${SUFFIX} -e npm_config_cache=${ROOT_DIR}/.cache/npm ${IMAGE_NODEJS} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    openDocumentation)
        extensionKey="${1}"
        if [ -n "${extensionKey}" ]; then
            if [[ ! -d "documentation-rendered/${extensionKey}/Documentation-GENERATED-temp" ]]; then
                echo "Error: Valid extension \"${extensionKey}\" does not contain a rendered \"Documentation\" folder"
                SUITE_EXIT_CODE=1
            else
                openDocumentation "${extensionKey}"
                SUITE_EXIT_CODE=$?
            fi
        else
            echo "Error: No extension key provided as first argument"
            SUITE_EXIT_CODE=1
        fi
        ;;
    phpstan)
        PHPSTAN_CONFIG_FILE="Build/phpstan/Core${CORE_VERSION}/phpstan.neon"
        COMMAND=(php -dxdebug.mode=off .Build/bin/phpstan analyse -c ${PHPSTAN_CONFIG_FILE} --no-progress --no-interaction --memory-limit 4G "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name phpstan-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    phpstanGenerateBaseline)
        PHPSTAN_CONFIG_FILE="Build/phpstan/Core${CORE_VERSION}/phpstan.neon"
        COMMAND=(php -dxdebug.mode=off .Build/bin/phpstan analyse -c ${PHPSTAN_CONFIG_FILE} --no-progress --no-interaction --memory-limit 4G --allow-empty-baseline --generate-baseline=Build/phpstan/Core${CORE_VERSION}/phpstan-baseline.neon)
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name phpstan-baseline-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    unit)
        PHPUNIT_CONFIG_FILE="Build/phpunit/UnitTests.xml"
        COMMAND=(.Build/bin/phpunit -c ${PHPUNIT_CONFIG_FILE} --exclude-group not-core-${CORE_VERSION} "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name unit-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    unitRandom)
        PHPUNIT_CONFIG_FILE="Build/phpunit/UnitTests.xml"
        COMMAND=(.Build/bin/phpunit -c ${PHPUNIT_CONFIG_FILE} --exclude-group not-core-${CORE_VERSION} --order-by=random ${PHPUNIT_RANDOM} "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name unit-random-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    update)
        # pull typo3/core-testing-* versions of those ones that exist locally
        echo "> pull ghcr.io/typo3/core-testing-* versions of those ones that exist locally"
        ${CONTAINER_BIN} images "ghcr.io/typo3/core-testing-*" --format "{{.Repository}}:{{.Tag}}" | xargs -I {} ${CONTAINER_BIN} pull {}
        echo ""
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        echo "> remove \"dangling\" ghcr.io/typo3/core-testing-* images (those tagged as <none>)"
        ${CONTAINER_BIN} images --filter "reference=ghcr.io/typo3/core-testing-*" --filter "dangling=true" --format "{{.ID}}" | xargs -I {} ${CONTAINER_BIN} rmi -f {}
        echo ""
        ;;
    help)
        loadHelp
        echo "${HELP}" >&2
        exit 0
        ;;
    *)
        loadHelp
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
        ;;
esac

# Cleanup, print summary && exit with exitcode
printSummary
