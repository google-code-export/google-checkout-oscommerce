#!/usr/bin/python2.4
#
# Copyright 2009 Google Inc. All Rights Reserved.

"""Install script for the Google Checkout OS Commerce plugin.

"""

__author__ = 'alek.dembowski@gmail.com (Alek Dembowski)'

import filecmp
import logging
import optparse
import os
import ScrolledText
import shutil
import StringIO
import subprocess
import sys
import tempfile
import Tkinter
import tkFileDialog
import tkMessageBox

MERGE_MARKER = '<<<<<<<'

MANUAL_DOCS = 'http://code.google.com/p/google-checkout-oscommerce/wiki/Documentation'

class WizardBuilder(object):
  def __init__(self, diff3, *args, **kwargs):
    super(WizardBuilder, self).__init__(*args, **kwargs)

    self.diff3 = diff3
    self.row = 0
    self.window = Tkinter.Tk()
    self.window.title('Checkout Plugin Installer')

    self.welcome_screen = self.build_config_screen(self.window)
#        self.buildProgressScreen(window),
#        self.buildSuccessScreen(window),
#        self.buildFailureScreen(window)

    self.welcome_screen.pack()

  def show_error_window(self, error_text):
    window = Tkinter.Toplevel()
    window.title('Errors')

    label = Tkinter.Label(window,
                 text='Unable to install the Google Checkout Module for '
                 'OS Commerce due to errors in the files below.\nPlease '
                 'see %s for instructions on how to install the plugin '
                 'manually.' % MANUAL_DOCS)
    label.pack(side=Tkinter.TOP)

    text = ScrolledText.ScrolledText(window)
    text.insert('1.0', error_text)
    text.pack(side=Tkinter.BOTTOM, fill=Tkinter.BOTH, expand=1)
#    self.add_button(window, 2, 4, 'Close', window.withdraw)

  def build_config_screen(self, window):
    screen = Tkinter.Frame(window)

    self.add_row(screen, 0,
                 'Welcome to the Google Checkout installer for OS Commerce!')
    self.add_row(screen, 1,
                 'This installer works with OS Commerce version 2.2rc2')

    self.plugin_entry = self.add_directory_row(screen, 2, 'Plugin Directory')
    self.install_entry = self.add_directory_row(screen, 3, 'OSCommerce Direcory')

    self.add_button(screen, 4, 2, 'Install', self.confirm)
    self.add_button(screen, 4, 3, 'Cancel', sys.exit)

    return screen

  def add_row(self, frame, row, text):
    label = Tkinter.Label(frame, text=text)
    label.grid(row=row, column=0, columnspan=4)

  def add_directory_row(self, frame, row, label_text):
    label = Tkinter.Label(frame, text=label_text)
    label.grid(row=row, column=0)

    entry = Tkinter.Entry(frame)
    entry.grid(row=row, column=1, columnspan=2)

    def update():
      entry.delete(0, Tkinter.END)
      entry.insert(Tkinter.END, tkFileDialog.askdirectory())

    self.add_button(frame, row, 3, 'Browse', update)
    return entry

  def add_button(self, frame, row, column, text, command):
    button = Tkinter.Button(frame, text=text, command=command)
    button.grid(row=row, column=column)
    return button

  def confirm(self):
    if not self.plugin_entry.get().strip():
      return tkMessageBox.showerror(title='Unable to find directory',
          message='Please enter where you downloaded the '
                  'Google Checkout plugin.')
    elif not self.install_entry.get().strip():
      return tkMessageBox.showerror(title='Unable to find directory',
          message='Please enter where you installed OS Commerce.')

    backup_dir = tempfile.mkdtemp(prefix='checkout-osc-plugin')
    logging.info('Backup dir %s' % backup_dir)

    plugin_dir = os.path.join(self.plugin_entry.get(), 'catalog%s' % os.sep)
    install_dir = os.path.join(self.install_entry.get(), 'catalog%s' % os.sep)
    golden_dir = os.path.join(self.plugin_entry.get(), 'tools', 'golden',
                              'oscommerce-2.2rc2a', 'catalog%s' % os.sep)

    if not os.path.exists(plugin_dir):
      return tkMessageBox.showerror(title='Unable to find directory',
          message='Unable to find the checkout plugin. Please check '
                  'the directory and try again.')
    elif not os.path.exists(install_dir):
      return tkMessageBox.showerror(title='Unable to find directory',
          message='Unable to find the OS Commerce installation. '
                  'Please check the directory and try again.')
    elif not os.path.exists(golden_dir):
      return tkMessageBox.showerror(title='Unable to find directory',
          message='Unable to find the checkout plugin. Please check '
                  'the directory and try again.')

    backup(install_dir, backup_dir)
    try:
      problems = install(self.diff3, plugin_dir, golden_dir, install_dir)

      if problems:
        rollback(install_dir, backup_dir)

        self.show_error_window('\n\n'.join(['%s: %s' % (file, reason) for (reason, file) in problems]))
        return
    except Exception, e:
      logging.error('Exception occured: %s' % e, exc_info=e)
      rollback(install_dir, backup_dir)
      return

    shutil.rmtree(backup_dir)
    tkMessageBox.showinfo(title='Installation Sucessful', 
                          message='The Google Checkout plugin for OS Commerce '
                          'installed successfully. Please verify and activate '
                          'it through the OS Commerce admin interface.')
    sys.exit()


def install_file(diff3, plugin, golden, destination):
  '''Installs the plugin file to the destination directory.

  For each file, if it only exists in the plugin, or is identical to the
  install we pass, We only do the diff it it exists in all three places
  Plugin           Install                Result
    Changed          Same                   Merge
                     Changed                Merge
                     Deleted                Warn
    New              New                    Okay IFF contents match
                     Doesn't Exist          Copy

  Expects the path to the plugin's changed file, the golden file and the
  destination file.

  Straight copy if it doesn't exist in the destination, merge if it does
  exist.

  Returns True iff there are no conflicts or warnings.

  Returns None if there are no errors or a tuple with the error and file name
          there was an error with

  TODO(alek.dembowski) This does not do any error checking... at a minimum we
  should be checking that the subprocess completed properly, that the
  destination file is writable, and catching IO exceptions during the write.
  '''
  if os.path.isdir(plugin):
    return None

  if not os.path.exists(destination):
    make_dir(os.path.dirname(destination))
    shutil.copy2(plugin, destination)
    os.chmod(destination, 0775)

    if os.path.exists(golden):
      return ('This file has been removed from your installation and is '
              'required for the plugin.', destination)
  elif not os.path.exists(golden):
    # Touchy here... we should only be okay with this case if we know the files
    # are the same...
    if filecmp.cmp(plugin, destination):
      return None
    else:
      return ('File unexpectedly exists in the installation directory. '
              'This may mean that the plugin has been installed once '
              'before already.', destination)


  if filecmp.cmp(plugin, destination):
    # It seems we already applied the changes
    return None

  # Apply the merge to the destination file
  output = merge(diff3, plugin, golden, destination)

  # Open dest file and write the buffer to it
  dest = open(destination, mode='w')
  dest.write(output)
  dest.close()
  os.chmod(destination, 0775)

  # Test for merge markers
  if output.find(MERGE_MARKER) >= 0:
    return ('This file appears to have been modified and we can\'t resolve '
            'the differences' , destination)

  return None


def merge(diff3, plugin, golden, destination):
  logging.info('Running %s on the three input files, %s, %s, and %s' %
               (diff3, plugin, golden, destination))
  merger = subprocess.Popen([diff3, '-m', plugin, golden, destination],
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
  (out, error) = merger.communicate()

  return out


def remove_left(original, removed):
  if original.find(removed) >= 0:
    size = len(removed)
    return original[size:]

  return original


def list_files(root):
  files = []

  for dirpath, dirnames, filenames in os.walk(root):
    if dirpath.find('.svn') < 0:
      # Strip the root from the path so we have the relative dir
      relative_path = remove_left(dirpath, root)

      for name in filenames:
        files.append(os.path.join(relative_path, name))

  return files


def install(diff3, plugin, golden, install):
  plugin_files = list_files(plugin)

  # Some files in the plugin need to be set to be writable... this feels hacky
  # but is currently how the plugin is written. Should think of better ways to
  # do this going forward.
  writable_files = [
    os.path.join(install, 'googlecheckout', 'logs', 'response_error.log'),
    os.path.join(install, 'googlecheckout', 'logs', 'response_message.log'),
  ]

  errors = []

  fail = False
  for file in plugin_files:
    try:
      plugin_file = '%s.%s%s' % (plugin, os.sep, file)
      golden_file = '%s.%s%s' % (golden, os.sep, file)
      existing_file = '%s.%s%s' % (install, os.sep, file)

      error = install_file(diff3, plugin_file, golden_file, existing_file)

      if error:
        errors.append(error)
      elif os.path.exists(existing_file) and existing_file in writable_files:
        os.chmod(existing_file, 0777)

    except Exception, error:
      logging.error('Error while installing file: %s' % file, exc_info=error)
      errors.append(('Error occured: %s' % error, existing_file))

  return errors


def make_dir(path):
  # This really is a work around for makedirs not behaving as specified. The
  # docs say it will make all the required directories with permissions of 777,
  # but this doesn't appear to be consistently honored.
  if not os.path.exists(path):
    path = path.rstrip(os.sep)
    make_dir(os.path.dirname(path))
    os.mkdir(path)

    os.chmod(path, 0755)


def backup(install_dir, backup_dir):
  shutil.copytree(install_dir, os.path.join(backup_dir, 'backup'))


def rollback(install_dir, backup_dir):
  if (os.path.exists(os.path.join(backup_dir, 'backup'))
      and os.path.isdir(os.path.join(backup_dir, 'backup'))):
    shutil.rmtree(install_dir)
    shutil.copytree(os.path.join(backup_dir, 'backup'), install_dir)


def main():
  p = optparse.OptionParser()
  p.add_option('-q', '--quiet', action='store_true')
  p.add_option('-d', '--diff3', action='store', default='diff3',
               help='Path to the diff3 executable. Assumes diff3 '
                    'exists on the system path otherwise.')
  p.add_option('-b', '--backup', action='store',
               help='Specify a directory to store the backup in')
  p.add_option('-u', '--ui', action='store_true',
               help='Runs the script using the UI wizard')
  p.add_option('-r', '--restore', action='store',
               help='Specifies the directory the backup is ')

  options, args =  p.parse_args()
  if not options.quiet:
    logging.getLogger().setLevel(logging.ERROR)
  else:
    logging.getLogger().setLevel(logging.DEBUG)
    if options.diff3:
      logging.info('Diff3 option passed: %s' % options.diff3)

  if options.restore:
    if len(args) > 1:
      logging.error('Too many arguments for restoring from a backup, expected '
                    '<installation directory>')
    elif len(args) < 1:
      logging.error('Too few arguments, expected '
                    '<installation directory>')
    else:
      rollback(args[0], args[1])
  elif options.ui:
    builder = WizardBuilder(options.diff3)
    Tkinter.mainloop()
  else:
    if len(args) < 3:
      print ('Not enough arguments supplied, expects '
             '<checkout plugin> <clean oscommerce> <existing oscommerce install>')
      return
    elif len(args) > 3:
      print ('Too many args provided, expects '
             '<checkout plugin> <clean oscommerce> <existing oscommerce install>')
      return

    print('Beginning install process')

    plugin_dir = args[0]
    golden_dir = args[1]
    install_dir = args[2]
    if options.backup:
      backup_dir = os.path.realpath(options.backup)

      if os.path.exists(backup_dir):
        shutil.rmtree(backup_dir)
        make_dir(os.path.dirname(backup_dir.rstrip(os.sep)))
    else:
      backup_dir = tempfile.mkdtemp(prefix='checkout-osc-plugin')
      logging.info('Backup dir: %s' % backup_dir)

    try:
      backup(install_dir, backup_dir)
    finally:
      if not options.backup:
        # In this case the backup was only temporary
        shutil.rmtree(backup_dir)
    install(options.diff3, plugin_dir, golden_dir, install_dir)


if __name__ == '__main__':
  main()
