pipeline {
    agent {
        label 'master'
    }
    stages {
        stage('Tests') {
            agent {
                docker {
                    image 'alexwijn/docker-git-php-composer'
                    reuseNode true
                }
            }
            environment {
                HOME = '.'
            }
            options {
                skipDefaultCheckout()
            }
            steps {
                sh(
                    label: 'Install/Update sources from Composer',
                    script: "composer update --no-interaction --no-ansi --no-progress"
                )
                sh(
                    label: 'Run backend tests',
                    script: './vendor/phpunit/phpunit/phpunit test/unit'
                )
            }
        }
    }
}
