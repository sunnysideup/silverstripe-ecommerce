                    </div>
                </main>
            <footer>
                <div class="content">
                    <% if $Menu %>
                    <h2>Quick Update Screens</h2>
                    <ul>
                        <% loop Menu %>
                        <li><a href="$Link">$Title</a></li>
                        <% end_loop %>
                    </ul>
                    <% else %>
                        <p class="message warning">
                            Sorry, there are no quick-updates available.
                        </p>
                    <% end_if %>
                </div>
            </footer>
        </div>
    </body>
</html>
