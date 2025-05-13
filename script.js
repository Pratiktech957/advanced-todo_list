 // Priority selector
        const priorityOptions = document.querySelectorAll('.priority-option');
        const priorityInput = document.getElementById('priority-value');
        
        priorityOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                priorityOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Update hidden input value
                priorityInput.value = this.getAttribute('data-priority');
            });
        });

        // Filter functionality
        const filterDropdown = document.getElementById('filter-todos');
        const todoItems = document.querySelectorAll('.todo-item');
        
        filterDropdown.addEventListener('change', function() {
            const filterValue = this.value;
            
            todoItems.forEach(item => {
                if (filterValue === 'all') {
                    item.style.display = 'flex';
                } else if (filterValue === 'active' && !item.classList.contains('completed')) {
                    item.style.display = 'flex';
                } else if (filterValue === 'completed' && item.classList.contains('completed')) {
                    item.style.display = 'flex';
                } else if (item.classList.contains('priority-' + filterValue)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Three.js implementation
        const container = document.getElementById('canvas-container');
        
        // Scene setup
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        
        const renderer = new THREE.WebGLRenderer({ alpha: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setClearColor(0x000000, 0);
        container.appendChild(renderer.domElement);
        
        // Create floating cubes
        const cubes = [];
        const cubeCount = 15;
        
        for (let i = 0; i < cubeCount; i++) {
            const geometry = new THREE.BoxGeometry(1, 1, 1);
            const material = new THREE.MeshBasicMaterial({ 
                color: new THREE.Color(
                    0.5 + Math.random() * 0.5, 
                    0.5 + Math.random() * 0.5, 
                    0.5 + Math.random() * 0.5
                ),
                transparent: true,
                opacity: 0.3,
                wireframe: Math.random() > 0.5
            });
            
            const cube = new THREE.Mesh(geometry, material);
            
            // Distribute cubes randomly within the container
            cube.position.x = (Math.random() - 0.5) * 20;
            cube.position.y = (Math.random() - 0.5) * 10;
            cube.position.z = (Math.random() - 0.5) * 15;
            
            // Give random rotation
            cube.rotation.x = Math.random() * Math.PI;
            cube.rotation.y = Math.random() * Math.PI;
            
            // Store animation info
            cube.userData = {
                rotationSpeed: {
                    x: (Math.random() - 0.5) * 0.02,
                    y: (Math.random() - 0.5) * 0.02
                },
                floatSpeed: 0.005 + Math.random() * 0.01,
                floatDirection: Math.random() > 0.5 ? 1 : -1,
                floatDistance: 0.5 + Math.random()
            };
            
            scene.add(cube);
            cubes.push(cube);
        }
        
        // Position camera
        camera.position.z = 15;
        
        // Animation loop
        function animate() {
            requestAnimationFrame(animate);
            
            // Animate cubes
            cubes.forEach(cube => {
                cube.rotation.x += cube.userData.rotationSpeed.x;
                cube.rotation.y += cube.userData.rotationSpeed.y;
                
                // Floating animation
                cube.position.y += cube.userData.floatSpeed * cube.userData.floatDirection;
                
                // Change direction if moved too far
                if (Math.abs(cube.position.y) > cube.userData.floatDistance) {
                    cube.userData.floatDirection *= -1;
                }
            });
            
            renderer.render(scene, camera);
        }
        
        // Handle window resize
        window.addEventListener('resize', () => {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        });
        
        // Start animation
        animate();

        // AJAX functionality for task operations
        document.addEventListener('DOMContentLoaded', function() {
            // Complete task functionality
            document.querySelectorAll('.complete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const taskId = this.getAttribute('data-id');
                    const taskElement = document.getElementById('task-' + taskId);
                    const loader = document.getElementById('loader');
                    
                    // Show loader
                    loader.style.display = 'block';
                    
                    // Send AJAX request
                    fetch('update_todo_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + taskId
                    })
                    .then(response => response.json())
                    .then(data => {
                        loader.style.display = 'none';
                        
                        if (data.success) {
                            // Toggle completed class
                            taskElement.classList.toggle('completed');
                            
                            // Update button icon
                            const btn = taskElement.querySelector('.complete-btn');
                            if (taskElement.classList.contains('completed')) {
                                btn.innerHTML = '↩️';
                                btn.title = 'Mark as Incomplete';
                            } else {
                                btn.innerHTML = '✓';
                                btn.title = 'Mark as Complete';
                            }
                        }
                    })
                    .catch(error => {
                        loader.style.display = 'none';
                        console.error('Error:', error);
                    });
                });
            });
            
            // Delete task functionality
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this task?')) {
                        const taskId = this.getAttribute('data-id');
                        const taskElement = document.getElementById('task-' + taskId);
                        const loader = document.getElementById('loader');
                        
                        // Show loader
                        loader.style.display = 'block';
                        
                        // Send AJAX request
                        fetch('delete_todo.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'id=' + taskId
                        })
                        .then(response => response.json())
                        .then(data => {
                            loader.style.display = 'none';
                            
                            if (data.success) {
                                // Remove task from DOM
                                taskElement.remove();
                            }
                        })
                        .catch(error => {
                            loader.style.display = 'none';
                            console.error('Error:', error);
                        });
                    }
                });
            });
        });