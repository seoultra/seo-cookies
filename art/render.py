#!/usr/bin/env python3
"""Renders cover.html to a 1200x630 PNG. Usage: python3 render.py OUT.png"""
import sys, pathlib
from playwright.sync_api import sync_playwright
src = pathlib.Path(__file__).with_name('cover.html').resolve().as_uri()
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': 1200, 'height': 630})
    pg.goto(src, wait_until='load')
    pg.screenshot(path=sys.argv[1], type='png')
    b.close()
