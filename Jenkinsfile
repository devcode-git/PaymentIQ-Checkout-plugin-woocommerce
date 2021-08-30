pipeline {
    agent any
    environment {
        VERSION = '0.0.0'
        WORDPRESS_DEVCODE_PWD = credentials('wordpress-devopspaymentiq-pwd')
    }
    options {
        buildDiscarder(logRotator(numToKeepStr: '1'))
        timeout(time: 1, unit: 'HOURS')
        disableConcurrentBuilds()
    }
  
    stages {
        stage('Build new version') {
            steps {
                script {
                    if (env.BRANCH_NAME == "master") {
                        sh 'node --version'
                        docker.image('node:15.0').inside("-u 0:0") {
                            sh 'npm install'
                            sh 'npm run build'
                        }
                    } else {
                        sh 'npm run node-log' // Do nothing
                    }
                }
            }
        }
         stage('Publish to WordPress') {
            steps {
                script {
                    if (env.BRANCH_NAME == "master") {
                        VERSION = sh(script: "jq -r '.version' package.json", returnStdout: true).trim()
                        input message: "Deploy ${env.BRANCH_NAME} as ${$VERSION}?", ok: 'Yes'
                        ./jenkins_wpsvn_deploy.sh devopspaymentiq $WORDPRESS_DEVCODE_PWD
                    } else {
                        sh 'npm run node-log' // Do nothing
                    }
                }
            }
        }
    }
}