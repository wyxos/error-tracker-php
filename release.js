(async function () {
    const { execSync } = require('child_process')
    const prompt = require('prompt')

    // read package.json
    // get version
    const currentVersion = execSync('git describe --tags --abbrev=0').toString()

    // prompt user for version
    prompt.start()
    const { version } = await prompt.get({
        properties: {
            version: {
                description: 'Enter the version to publish:',
                default: currentVersion,
                before: function(value) { return 'v' + value; }
            }
        }
    })

    execSync('git add .')
    console.log('Files staged.')

    execSync(`git commit -m "feat: release ${version}"`)
    console.log('Files committed.')

    execSync(`git tag ${version}`)
    console.log('Tag version added.')

    execSync(`git push origin ${version}`)
    console.log('Push complete.')
}())
