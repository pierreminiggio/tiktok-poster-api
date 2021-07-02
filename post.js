import post from '@pierreminiggio/tiktok-poster'

const args = process.argv

if (args.length !== 6 && args.length !== 7) {
    console.log('Use like this : node post.js [fbLogin] [fbPassword] [videoFileName] [legend] [?proxy]')
    process.exit()
}

post(
    args[2],
    args[3],
    args[4],
    args[5],
    args.length === 7 ? args[6] : null
).then(videoLink => {
    console.log(videoLink)
}).catch(error => {
    console.log('error')
    console.log(error)
})
