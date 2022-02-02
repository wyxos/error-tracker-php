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

    const fs = require('fs')

    const json = JSON.parse(fs.readFileSync('./package.json'))

    json.version = version

    fs.writeFileSync('./package.json', JSON.stringify(json))

    execSync('git add .')
    console.log('Files staged.')

    execSync(`git commit -m "feat: release ${version}"`)
    console.log('Files committed.')

    execSync(`git tag ${version}`)
    console.log('Tag version added.')

    execSync(`git push origin ${version}`)
    console.log('Pushing tag.')

    execSync(`git push origin master`)
    console.log('Push complete.')
}())
